import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/sync/sync_engine.dart';
import '../../core/networking/api_client.dart';

class SyncService {
  final SyncEngine _syncEngine;

  SyncService(this._syncEngine);

  Future<void> sync() async {
    await _syncEngine.sync();
  }

  Future<void> queueCreate({
    required String entityType,
    required String entityId,
    required Map<String, dynamic> payload,
  }) async {
    await _syncEngine.queueOperation(
      entityType: entityType,
      entityId: entityId,
      operation: 'CREATE',
      payload: payload,
    );
  }

  Future<void> queueUpdate({
    required String entityType,
    required String entityId,
    required Map<String, dynamic> payload,
  }) async {
    await _syncEngine.queueOperation(
      entityType: entityType,
      entityId: entityId,
      operation: 'UPDATE',
      payload: payload,
    );
  }

  Future<void> queueDelete({
    required String entityType,
    required String entityId,
  }) async {
    await _syncEngine.queueOperation(
      entityType: entityType,
      entityId: entityId,
      operation: 'DELETE',
      payload: {},
    );
  }

  bool get isSyncing => _syncEngine.isSyncing;
}

final syncServiceProvider = Provider<SyncService>((ref) {
  return SyncService(SyncEngine(ref.read(apiClientProvider)));
});
