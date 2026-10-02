import 'package:freezed_annotation/freezed_annotation.dart';

part 'organization.freezed.dart';
part 'organization.g.dart';

@freezed
class Organization with _$Organization {
  const factory Organization({
    required String id,
    required String name,
    required String slug,
    required String timezone,
    required String currency,
    Map<String, dynamic>? settings,
    required String createdAt,
    required String updatedAt,
  }) = _Organization;

  factory Organization.fromJson(Map<String, dynamic> json) =>
      _$OrganizationFromJson(json);
}

@freezed
class Branch with _$Branch {
  const factory Branch({
    required String id,
    required String organizationId,
    required String name,
    String? address,
    String? phone,
    String? email,
    required bool isMain,
    required String createdAt,
    required String updatedAt,
  }) = _Branch;

  factory Branch.fromJson(Map<String, dynamic> json) => _$BranchFromJson(json);
}

@freezed
class User with _$User {
  const factory User({
    required String id,
    required String organizationId,
    required String name,
    required String email,
    required String role,
    required bool isActive,
    required String createdAt,
    required String updatedAt,
  }) = _User;

  factory User.fromJson(Map<String, dynamic> json) => _$UserFromJson(json);
}
