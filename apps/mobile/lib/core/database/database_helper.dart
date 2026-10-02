import 'package:sqflite/sqflite.dart';
import 'package:path/path.dart';
import 'package:path_provider/path_provider.dart';

class DatabaseHelper {
  static Database? _database;
  static const String _databaseName = 'flowos.db';
  static const int _databaseVersion = 1;

  DatabaseHelper._privateConstructor();
  static final DatabaseHelper instance = DatabaseHelper._privateConstructor();

  Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDatabase();
    return _database!;
  }

  Future<Database> _initDatabase() async {
    final documentsDirectory = await getApplicationDocumentsDirectory();
    final path = join(documentsDirectory.path, _databaseName);
    return await openDatabase(
      path,
      version: _databaseVersion,
      onCreate: _onCreate,
    );
  }

  Future<void> _onCreate(Database db, int version) async {
    // Organizations table (synced from server)
    await db.execute('''
      CREATE TABLE organizations (
        id TEXT PRIMARY KEY,
        name TEXT NOT NULL,
        slug TEXT NOT NULL,
        timezone TEXT NOT NULL,
        currency TEXT NOT NULL,
        settings TEXT,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        synced_at TEXT
      )
    ''');

    // Branches table
    await db.execute('''
      CREATE TABLE branches (
        id TEXT PRIMARY KEY,
        organization_id TEXT NOT NULL,
        name TEXT NOT NULL,
        address TEXT,
        phone TEXT,
        email TEXT,
        is_main INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        synced_at TEXT,
        FOREIGN KEY (organization_id) REFERENCES organizations (id)
      )
    ''');

    // Users table
    await db.execute('''
      CREATE TABLE users (
        id TEXT PRIMARY KEY,
        organization_id TEXT NOT NULL,
        name TEXT NOT NULL,
        email TEXT NOT NULL,
        role TEXT NOT NULL,
        is_active INTEGER NOT NULL DEFAULT 1,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        synced_at TEXT,
        FOREIGN KEY (organization_id) REFERENCES organizations (id)
      )
    ''');

    // Sync queue table for offline operations
    await db.execute('''
      CREATE TABLE sync_queue (
        id TEXT PRIMARY KEY,
        entity_type TEXT NOT NULL,
        entity_id TEXT NOT NULL,
        operation TEXT NOT NULL, -- CREATE, UPDATE, DELETE
        payload TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'pending', -- pending, synced, failed
        retry_count INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL
      )
    ''');

    // Create indexes
    await db
        .execute('CREATE INDEX idx_branches_org ON branches(organization_id)');
    await db.execute('CREATE INDEX idx_users_org ON users(organization_id)');
    await db
        .execute('CREATE INDEX idx_sync_queue_status ON sync_queue(status)');
  }

  Future<void> close() async {
    final db = await database;
    await db.close();
  }
}
