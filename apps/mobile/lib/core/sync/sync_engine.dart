import 'package:sqflite/sqflite.dart';
import 'package:uuid/uuid.dart';
import 'package:flowos_mobile/core/database/database_helper.dart';
import 'package:flowos_mobile/core/networking/api_client.dart';
import 'package:flowos_mobile/core/storage/local_storage.dart';
import 'package:flutter/foundation.dart';

class SyncEngine {
  static const Set<String> _supportedEntityTypes = {
    'organizations',
    'branches',
    'users',
  };

  static const Set<String> _supportedOperations = {
    'CREATE',
    'UPDATE',
    'DELETE',
  };

  final DatabaseHelper _dbHelper = DatabaseHelper.instance;
  final ApiClient _apiClient;
  bool _isSyncing = false;

  SyncEngine(this._apiClient);

  bool get isSyncing => _isSyncing;

  void _logError(String message) {
    if (kDebugMode) {
      // Exception text can include transport or server details. Do not emit it:
      // debug builds must not leak request credentials or response payloads.
      debugPrint('[SyncEngine] $message');
    }
  }

  Future<void> sync() async {
    if (_isSyncing) return;
    _isSyncing = true;

    try {
      await _pushLocalChanges();
      await _pullRemoteChanges();
      await LocalStorage.setString(
          LocalStorage.keyLastSyncAt, DateTime.now().toIso8601String());
    } catch (_) {
      // Log error but don't throw - offline-first means we continue operating
      _logError('Sync failed');
    } finally {
      _isSyncing = false;
    }
  }

  Future<void> _pushLocalChanges() async {
    final db = await _dbHelper.database;
    final pendingChanges = await db.query(
      'sync_queue',
      where: 'status = ?',
      whereArgs: ['pending'],
      orderBy: 'created_at ASC',
    );

    for (final change in pendingChanges) {
      try {
        final entityType = change['entity_type'] as String;
        final entityId = change['entity_id'] as String;
        final operation = change['operation'] as String;
        final payload = change['payload'] as String;

        await _executeOperation(entityType, entityId, operation, payload);

        await db.update(
          'sync_queue',
          {
            'status': 'synced',
            'updated_at': DateTime.now().toIso8601String(),
          },
          where: 'id = ?',
          whereArgs: [change['id']],
        );
      } catch (e) {
        await db.update(
          'sync_queue',
          {
            'status': 'failed',
            'retry_count': (change['retry_count'] as int) + 1,
            'updated_at': DateTime.now().toIso8601String(),
          },
          where: 'id = ?',
          whereArgs: [change['id']],
        );
      }
    }
  }

  Future<void> _executeOperation(
    String entityType,
    String entityId,
    String operation,
    String payload,
  ) async {
    _assertSupportedOperation(entityType, operation);

    String endpoint = '/$entityType';
    if (operation != 'CREATE') {
      endpoint = '/$entityType/$entityId';
    }

    final data =
        payload.isNotEmpty ? Uri.parse('?$payload').queryParameters : null;

    switch (operation) {
      case 'CREATE':
        await _apiClient.post(endpoint, data: data);
        break;
      case 'UPDATE':
        await _apiClient.patch(endpoint, data: data);
        break;
      case 'DELETE':
        await _apiClient.delete(endpoint);
        break;
    }
  }

  Future<void> _pullRemoteChanges() async {
    final lastSync = LocalStorage.getString(LocalStorage.keyLastSyncAt);
    final since = lastSync ??
        DateTime.now().subtract(const Duration(days: 30)).toIso8601String();

    // Pull organizations
    await _pullEntity('organizations', since);
    // Pull branches
    await _pullEntity('branches', since);
    // Pull users
    await _pullEntity('users', since);
    // Add more entities as needed
  }

  Future<void> _pullEntity(String entityType, String since) async {
    try {
      final response = await _apiClient.get('/$entityType', queryParameters: {
        'since': since,
        'per_page': 100,
      });

      final items = response['data'] as List?;
      if (items == null) return;

      final db = await _dbHelper.database;
      final batch = db.batch();

      for (final item in items) {
        final itemMap = Map<String, dynamic>.from(item);
        itemMap['synced_at'] = DateTime.now().toIso8601String();

        batch.insert(
          entityType,
          itemMap,
          conflictAlgorithm: ConflictAlgorithm.replace,
        );
      }

      await batch.commit(noResult: true);
    } catch (_) {
      _logError('Failed to pull $entityType');
    }
  }

  Future<void> queueOperation({
    required String entityType,
    required String entityId,
    required String operation,
    required Map<String, dynamic> payload,
  }) async {
    _assertSupportedOperation(entityType, operation);

    final db = await _dbHelper.database;
    await db.insert('sync_queue', {
      'id': Uuid().v4(),
      'entity_type': entityType,
      'entity_id': entityId,
      'operation': operation,
      'payload': Uri(queryParameters: payload).query,
      'status': 'pending',
      'retry_count': 0,
      'created_at': DateTime.now().toIso8601String(),
      'updated_at': DateTime.now().toIso8601String(),
    });
  }

  void _assertSupportedOperation(String entityType, String operation) {
    if (!_supportedEntityTypes.contains(entityType) ||
        !_supportedOperations.contains(operation)) {
      throw ArgumentError('Unsupported sync operation.');
    }
  }
}
