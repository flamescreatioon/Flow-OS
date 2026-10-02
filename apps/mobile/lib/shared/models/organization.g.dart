// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'organization.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_$OrganizationImpl _$$OrganizationImplFromJson(Map<String, dynamic> json) =>
    _$OrganizationImpl(
      id: json['id'] as String,
      name: json['name'] as String,
      slug: json['slug'] as String,
      timezone: json['timezone'] as String,
      currency: json['currency'] as String,
      settings: json['settings'] as Map<String, dynamic>?,
      createdAt: json['createdAt'] as String,
      updatedAt: json['updatedAt'] as String,
    );

Map<String, dynamic> _$$OrganizationImplToJson(_$OrganizationImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'slug': instance.slug,
      'timezone': instance.timezone,
      'currency': instance.currency,
      'settings': instance.settings,
      'createdAt': instance.createdAt,
      'updatedAt': instance.updatedAt,
    };

_$BranchImpl _$$BranchImplFromJson(Map<String, dynamic> json) => _$BranchImpl(
      id: json['id'] as String,
      organizationId: json['organizationId'] as String,
      name: json['name'] as String,
      address: json['address'] as String?,
      phone: json['phone'] as String?,
      email: json['email'] as String?,
      isMain: json['isMain'] as bool,
      createdAt: json['createdAt'] as String,
      updatedAt: json['updatedAt'] as String,
    );

Map<String, dynamic> _$$BranchImplToJson(_$BranchImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'organizationId': instance.organizationId,
      'name': instance.name,
      'address': instance.address,
      'phone': instance.phone,
      'email': instance.email,
      'isMain': instance.isMain,
      'createdAt': instance.createdAt,
      'updatedAt': instance.updatedAt,
    };

_$UserImpl _$$UserImplFromJson(Map<String, dynamic> json) => _$UserImpl(
      id: json['id'] as String,
      organizationId: json['organizationId'] as String,
      name: json['name'] as String,
      email: json['email'] as String,
      role: json['role'] as String,
      isActive: json['isActive'] as bool,
      createdAt: json['createdAt'] as String,
      updatedAt: json['updatedAt'] as String,
    );

Map<String, dynamic> _$$UserImplToJson(_$UserImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'organizationId': instance.organizationId,
      'name': instance.name,
      'email': instance.email,
      'role': instance.role,
      'isActive': instance.isActive,
      'createdAt': instance.createdAt,
      'updatedAt': instance.updatedAt,
    };
