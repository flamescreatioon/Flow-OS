import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/networking/api_client.dart';
import '../../core/database/database_helper.dart';
import '../../shared/models/organization.dart';

class OrganizationService {
  final ApiClient _apiClient;
  final DatabaseHelper _dbHelper = DatabaseHelper.instance;

  OrganizationService(this._apiClient);

  Future<List<Organization>> getOrganizations() async {
    try {
      final response = await _apiClient.get('/organizations');
      final data = response['data'] as List;
      return data.map((e) => Organization.fromJson(e)).toList();
    } catch (e) {
      // Fallback to local database
      return _getLocalOrganizations();
    }
  }

  Future<List<Organization>> _getLocalOrganizations() async {
    final db = await _dbHelper.database;
    final results = await db.query('organizations');
    return results.map((e) => Organization.fromJson(e)).toList();
  }

  Future<Organization?> getCurrentOrganization() async {
    // This would typically get the organization from auth context
    return null;
  }

  Future<Organization> createOrganization({
    required String name,
    required String slug,
    String timezone = 'Africa/Lagos',
    String currency = 'NGN',
  }) async {
    final response = await _apiClient.post('/organizations', data: {
      'name': name,
      'slug': slug,
      'timezone': timezone,
      'currency': currency,
    });
    return Organization.fromJson(response['data']);
  }
}

final organizationServiceProvider = Provider<OrganizationService>((ref) {
  return OrganizationService(ref.read(apiClientProvider));
});
