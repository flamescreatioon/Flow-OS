// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'organization.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
    'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models');

Organization _$OrganizationFromJson(Map<String, dynamic> json) {
  return _Organization.fromJson(json);
}

/// @nodoc
mixin _$Organization {
  String get id => throw _privateConstructorUsedError;
  String get name => throw _privateConstructorUsedError;
  String get slug => throw _privateConstructorUsedError;
  String get timezone => throw _privateConstructorUsedError;
  String get currency => throw _privateConstructorUsedError;
  Map<String, dynamic>? get settings => throw _privateConstructorUsedError;
  String get createdAt => throw _privateConstructorUsedError;
  String get updatedAt => throw _privateConstructorUsedError;

  /// Serializes this Organization to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of Organization
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $OrganizationCopyWith<Organization> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $OrganizationCopyWith<$Res> {
  factory $OrganizationCopyWith(
          Organization value, $Res Function(Organization) then) =
      _$OrganizationCopyWithImpl<$Res, Organization>;
  @useResult
  $Res call(
      {String id,
      String name,
      String slug,
      String timezone,
      String currency,
      Map<String, dynamic>? settings,
      String createdAt,
      String updatedAt});
}

/// @nodoc
class _$OrganizationCopyWithImpl<$Res, $Val extends Organization>
    implements $OrganizationCopyWith<$Res> {
  _$OrganizationCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of Organization
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? name = null,
    Object? slug = null,
    Object? timezone = null,
    Object? currency = null,
    Object? settings = freezed,
    Object? createdAt = null,
    Object? updatedAt = null,
  }) {
    return _then(_value.copyWith(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as String,
      name: null == name
          ? _value.name
          : name // ignore: cast_nullable_to_non_nullable
              as String,
      slug: null == slug
          ? _value.slug
          : slug // ignore: cast_nullable_to_non_nullable
              as String,
      timezone: null == timezone
          ? _value.timezone
          : timezone // ignore: cast_nullable_to_non_nullable
              as String,
      currency: null == currency
          ? _value.currency
          : currency // ignore: cast_nullable_to_non_nullable
              as String,
      settings: freezed == settings
          ? _value.settings
          : settings // ignore: cast_nullable_to_non_nullable
              as Map<String, dynamic>?,
      createdAt: null == createdAt
          ? _value.createdAt
          : createdAt // ignore: cast_nullable_to_non_nullable
              as String,
      updatedAt: null == updatedAt
          ? _value.updatedAt
          : updatedAt // ignore: cast_nullable_to_non_nullable
              as String,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$OrganizationImplCopyWith<$Res>
    implements $OrganizationCopyWith<$Res> {
  factory _$$OrganizationImplCopyWith(
          _$OrganizationImpl value, $Res Function(_$OrganizationImpl) then) =
      __$$OrganizationImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call(
      {String id,
      String name,
      String slug,
      String timezone,
      String currency,
      Map<String, dynamic>? settings,
      String createdAt,
      String updatedAt});
}

/// @nodoc
class __$$OrganizationImplCopyWithImpl<$Res>
    extends _$OrganizationCopyWithImpl<$Res, _$OrganizationImpl>
    implements _$$OrganizationImplCopyWith<$Res> {
  __$$OrganizationImplCopyWithImpl(
      _$OrganizationImpl _value, $Res Function(_$OrganizationImpl) _then)
      : super(_value, _then);

  /// Create a copy of Organization
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? name = null,
    Object? slug = null,
    Object? timezone = null,
    Object? currency = null,
    Object? settings = freezed,
    Object? createdAt = null,
    Object? updatedAt = null,
  }) {
    return _then(_$OrganizationImpl(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as String,
      name: null == name
          ? _value.name
          : name // ignore: cast_nullable_to_non_nullable
              as String,
      slug: null == slug
          ? _value.slug
          : slug // ignore: cast_nullable_to_non_nullable
              as String,
      timezone: null == timezone
          ? _value.timezone
          : timezone // ignore: cast_nullable_to_non_nullable
              as String,
      currency: null == currency
          ? _value.currency
          : currency // ignore: cast_nullable_to_non_nullable
              as String,
      settings: freezed == settings
          ? _value._settings
          : settings // ignore: cast_nullable_to_non_nullable
              as Map<String, dynamic>?,
      createdAt: null == createdAt
          ? _value.createdAt
          : createdAt // ignore: cast_nullable_to_non_nullable
              as String,
      updatedAt: null == updatedAt
          ? _value.updatedAt
          : updatedAt // ignore: cast_nullable_to_non_nullable
              as String,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$OrganizationImpl implements _Organization {
  const _$OrganizationImpl(
      {required this.id,
      required this.name,
      required this.slug,
      required this.timezone,
      required this.currency,
      final Map<String, dynamic>? settings,
      required this.createdAt,
      required this.updatedAt})
      : _settings = settings;

  factory _$OrganizationImpl.fromJson(Map<String, dynamic> json) =>
      _$$OrganizationImplFromJson(json);

  @override
  final String id;
  @override
  final String name;
  @override
  final String slug;
  @override
  final String timezone;
  @override
  final String currency;
  final Map<String, dynamic>? _settings;
  @override
  Map<String, dynamic>? get settings {
    final value = _settings;
    if (value == null) return null;
    if (_settings is EqualUnmodifiableMapView) return _settings;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableMapView(value);
  }

  @override
  final String createdAt;
  @override
  final String updatedAt;

  @override
  String toString() {
    return 'Organization(id: $id, name: $name, slug: $slug, timezone: $timezone, currency: $currency, settings: $settings, createdAt: $createdAt, updatedAt: $updatedAt)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$OrganizationImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.name, name) || other.name == name) &&
            (identical(other.slug, slug) || other.slug == slug) &&
            (identical(other.timezone, timezone) ||
                other.timezone == timezone) &&
            (identical(other.currency, currency) ||
                other.currency == currency) &&
            const DeepCollectionEquality().equals(other._settings, _settings) &&
            (identical(other.createdAt, createdAt) ||
                other.createdAt == createdAt) &&
            (identical(other.updatedAt, updatedAt) ||
                other.updatedAt == updatedAt));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(
      runtimeType,
      id,
      name,
      slug,
      timezone,
      currency,
      const DeepCollectionEquality().hash(_settings),
      createdAt,
      updatedAt);

  /// Create a copy of Organization
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$OrganizationImplCopyWith<_$OrganizationImpl> get copyWith =>
      __$$OrganizationImplCopyWithImpl<_$OrganizationImpl>(this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$OrganizationImplToJson(
      this,
    );
  }
}

abstract class _Organization implements Organization {
  const factory _Organization(
      {required final String id,
      required final String name,
      required final String slug,
      required final String timezone,
      required final String currency,
      final Map<String, dynamic>? settings,
      required final String createdAt,
      required final String updatedAt}) = _$OrganizationImpl;

  factory _Organization.fromJson(Map<String, dynamic> json) =
      _$OrganizationImpl.fromJson;

  @override
  String get id;
  @override
  String get name;
  @override
  String get slug;
  @override
  String get timezone;
  @override
  String get currency;
  @override
  Map<String, dynamic>? get settings;
  @override
  String get createdAt;
  @override
  String get updatedAt;

  /// Create a copy of Organization
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$OrganizationImplCopyWith<_$OrganizationImpl> get copyWith =>
      throw _privateConstructorUsedError;
}

Branch _$BranchFromJson(Map<String, dynamic> json) {
  return _Branch.fromJson(json);
}

/// @nodoc
mixin _$Branch {
  String get id => throw _privateConstructorUsedError;
  String get organizationId => throw _privateConstructorUsedError;
  String get name => throw _privateConstructorUsedError;
  String? get address => throw _privateConstructorUsedError;
  String? get phone => throw _privateConstructorUsedError;
  String? get email => throw _privateConstructorUsedError;
  bool get isMain => throw _privateConstructorUsedError;
  String get createdAt => throw _privateConstructorUsedError;
  String get updatedAt => throw _privateConstructorUsedError;

  /// Serializes this Branch to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of Branch
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $BranchCopyWith<Branch> get copyWith => throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $BranchCopyWith<$Res> {
  factory $BranchCopyWith(Branch value, $Res Function(Branch) then) =
      _$BranchCopyWithImpl<$Res, Branch>;
  @useResult
  $Res call(
      {String id,
      String organizationId,
      String name,
      String? address,
      String? phone,
      String? email,
      bool isMain,
      String createdAt,
      String updatedAt});
}

/// @nodoc
class _$BranchCopyWithImpl<$Res, $Val extends Branch>
    implements $BranchCopyWith<$Res> {
  _$BranchCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of Branch
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? organizationId = null,
    Object? name = null,
    Object? address = freezed,
    Object? phone = freezed,
    Object? email = freezed,
    Object? isMain = null,
    Object? createdAt = null,
    Object? updatedAt = null,
  }) {
    return _then(_value.copyWith(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as String,
      organizationId: null == organizationId
          ? _value.organizationId
          : organizationId // ignore: cast_nullable_to_non_nullable
              as String,
      name: null == name
          ? _value.name
          : name // ignore: cast_nullable_to_non_nullable
              as String,
      address: freezed == address
          ? _value.address
          : address // ignore: cast_nullable_to_non_nullable
              as String?,
      phone: freezed == phone
          ? _value.phone
          : phone // ignore: cast_nullable_to_non_nullable
              as String?,
      email: freezed == email
          ? _value.email
          : email // ignore: cast_nullable_to_non_nullable
              as String?,
      isMain: null == isMain
          ? _value.isMain
          : isMain // ignore: cast_nullable_to_non_nullable
              as bool,
      createdAt: null == createdAt
          ? _value.createdAt
          : createdAt // ignore: cast_nullable_to_non_nullable
              as String,
      updatedAt: null == updatedAt
          ? _value.updatedAt
          : updatedAt // ignore: cast_nullable_to_non_nullable
              as String,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$BranchImplCopyWith<$Res> implements $BranchCopyWith<$Res> {
  factory _$$BranchImplCopyWith(
          _$BranchImpl value, $Res Function(_$BranchImpl) then) =
      __$$BranchImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call(
      {String id,
      String organizationId,
      String name,
      String? address,
      String? phone,
      String? email,
      bool isMain,
      String createdAt,
      String updatedAt});
}

/// @nodoc
class __$$BranchImplCopyWithImpl<$Res>
    extends _$BranchCopyWithImpl<$Res, _$BranchImpl>
    implements _$$BranchImplCopyWith<$Res> {
  __$$BranchImplCopyWithImpl(
      _$BranchImpl _value, $Res Function(_$BranchImpl) _then)
      : super(_value, _then);

  /// Create a copy of Branch
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? organizationId = null,
    Object? name = null,
    Object? address = freezed,
    Object? phone = freezed,
    Object? email = freezed,
    Object? isMain = null,
    Object? createdAt = null,
    Object? updatedAt = null,
  }) {
    return _then(_$BranchImpl(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as String,
      organizationId: null == organizationId
          ? _value.organizationId
          : organizationId // ignore: cast_nullable_to_non_nullable
              as String,
      name: null == name
          ? _value.name
          : name // ignore: cast_nullable_to_non_nullable
              as String,
      address: freezed == address
          ? _value.address
          : address // ignore: cast_nullable_to_non_nullable
              as String?,
      phone: freezed == phone
          ? _value.phone
          : phone // ignore: cast_nullable_to_non_nullable
              as String?,
      email: freezed == email
          ? _value.email
          : email // ignore: cast_nullable_to_non_nullable
              as String?,
      isMain: null == isMain
          ? _value.isMain
          : isMain // ignore: cast_nullable_to_non_nullable
              as bool,
      createdAt: null == createdAt
          ? _value.createdAt
          : createdAt // ignore: cast_nullable_to_non_nullable
              as String,
      updatedAt: null == updatedAt
          ? _value.updatedAt
          : updatedAt // ignore: cast_nullable_to_non_nullable
              as String,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$BranchImpl implements _Branch {
  const _$BranchImpl(
      {required this.id,
      required this.organizationId,
      required this.name,
      this.address,
      this.phone,
      this.email,
      required this.isMain,
      required this.createdAt,
      required this.updatedAt});

  factory _$BranchImpl.fromJson(Map<String, dynamic> json) =>
      _$$BranchImplFromJson(json);

  @override
  final String id;
  @override
  final String organizationId;
  @override
  final String name;
  @override
  final String? address;
  @override
  final String? phone;
  @override
  final String? email;
  @override
  final bool isMain;
  @override
  final String createdAt;
  @override
  final String updatedAt;

  @override
  String toString() {
    return 'Branch(id: $id, organizationId: $organizationId, name: $name, address: $address, phone: $phone, email: $email, isMain: $isMain, createdAt: $createdAt, updatedAt: $updatedAt)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$BranchImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.organizationId, organizationId) ||
                other.organizationId == organizationId) &&
            (identical(other.name, name) || other.name == name) &&
            (identical(other.address, address) || other.address == address) &&
            (identical(other.phone, phone) || other.phone == phone) &&
            (identical(other.email, email) || other.email == email) &&
            (identical(other.isMain, isMain) || other.isMain == isMain) &&
            (identical(other.createdAt, createdAt) ||
                other.createdAt == createdAt) &&
            (identical(other.updatedAt, updatedAt) ||
                other.updatedAt == updatedAt));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(runtimeType, id, organizationId, name,
      address, phone, email, isMain, createdAt, updatedAt);

  /// Create a copy of Branch
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$BranchImplCopyWith<_$BranchImpl> get copyWith =>
      __$$BranchImplCopyWithImpl<_$BranchImpl>(this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$BranchImplToJson(
      this,
    );
  }
}

abstract class _Branch implements Branch {
  const factory _Branch(
      {required final String id,
      required final String organizationId,
      required final String name,
      final String? address,
      final String? phone,
      final String? email,
      required final bool isMain,
      required final String createdAt,
      required final String updatedAt}) = _$BranchImpl;

  factory _Branch.fromJson(Map<String, dynamic> json) = _$BranchImpl.fromJson;

  @override
  String get id;
  @override
  String get organizationId;
  @override
  String get name;
  @override
  String? get address;
  @override
  String? get phone;
  @override
  String? get email;
  @override
  bool get isMain;
  @override
  String get createdAt;
  @override
  String get updatedAt;

  /// Create a copy of Branch
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$BranchImplCopyWith<_$BranchImpl> get copyWith =>
      throw _privateConstructorUsedError;
}

User _$UserFromJson(Map<String, dynamic> json) {
  return _User.fromJson(json);
}

/// @nodoc
mixin _$User {
  String get id => throw _privateConstructorUsedError;
  String get organizationId => throw _privateConstructorUsedError;
  String get name => throw _privateConstructorUsedError;
  String get email => throw _privateConstructorUsedError;
  String get role => throw _privateConstructorUsedError;
  bool get isActive => throw _privateConstructorUsedError;
  String get createdAt => throw _privateConstructorUsedError;
  String get updatedAt => throw _privateConstructorUsedError;

  /// Serializes this User to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of User
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $UserCopyWith<User> get copyWith => throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $UserCopyWith<$Res> {
  factory $UserCopyWith(User value, $Res Function(User) then) =
      _$UserCopyWithImpl<$Res, User>;
  @useResult
  $Res call(
      {String id,
      String organizationId,
      String name,
      String email,
      String role,
      bool isActive,
      String createdAt,
      String updatedAt});
}

/// @nodoc
class _$UserCopyWithImpl<$Res, $Val extends User>
    implements $UserCopyWith<$Res> {
  _$UserCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of User
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? organizationId = null,
    Object? name = null,
    Object? email = null,
    Object? role = null,
    Object? isActive = null,
    Object? createdAt = null,
    Object? updatedAt = null,
  }) {
    return _then(_value.copyWith(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as String,
      organizationId: null == organizationId
          ? _value.organizationId
          : organizationId // ignore: cast_nullable_to_non_nullable
              as String,
      name: null == name
          ? _value.name
          : name // ignore: cast_nullable_to_non_nullable
              as String,
      email: null == email
          ? _value.email
          : email // ignore: cast_nullable_to_non_nullable
              as String,
      role: null == role
          ? _value.role
          : role // ignore: cast_nullable_to_non_nullable
              as String,
      isActive: null == isActive
          ? _value.isActive
          : isActive // ignore: cast_nullable_to_non_nullable
              as bool,
      createdAt: null == createdAt
          ? _value.createdAt
          : createdAt // ignore: cast_nullable_to_non_nullable
              as String,
      updatedAt: null == updatedAt
          ? _value.updatedAt
          : updatedAt // ignore: cast_nullable_to_non_nullable
              as String,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$UserImplCopyWith<$Res> implements $UserCopyWith<$Res> {
  factory _$$UserImplCopyWith(
          _$UserImpl value, $Res Function(_$UserImpl) then) =
      __$$UserImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call(
      {String id,
      String organizationId,
      String name,
      String email,
      String role,
      bool isActive,
      String createdAt,
      String updatedAt});
}

/// @nodoc
class __$$UserImplCopyWithImpl<$Res>
    extends _$UserCopyWithImpl<$Res, _$UserImpl>
    implements _$$UserImplCopyWith<$Res> {
  __$$UserImplCopyWithImpl(_$UserImpl _value, $Res Function(_$UserImpl) _then)
      : super(_value, _then);

  /// Create a copy of User
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? organizationId = null,
    Object? name = null,
    Object? email = null,
    Object? role = null,
    Object? isActive = null,
    Object? createdAt = null,
    Object? updatedAt = null,
  }) {
    return _then(_$UserImpl(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as String,
      organizationId: null == organizationId
          ? _value.organizationId
          : organizationId // ignore: cast_nullable_to_non_nullable
              as String,
      name: null == name
          ? _value.name
          : name // ignore: cast_nullable_to_non_nullable
              as String,
      email: null == email
          ? _value.email
          : email // ignore: cast_nullable_to_non_nullable
              as String,
      role: null == role
          ? _value.role
          : role // ignore: cast_nullable_to_non_nullable
              as String,
      isActive: null == isActive
          ? _value.isActive
          : isActive // ignore: cast_nullable_to_non_nullable
              as bool,
      createdAt: null == createdAt
          ? _value.createdAt
          : createdAt // ignore: cast_nullable_to_non_nullable
              as String,
      updatedAt: null == updatedAt
          ? _value.updatedAt
          : updatedAt // ignore: cast_nullable_to_non_nullable
              as String,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$UserImpl implements _User {
  const _$UserImpl(
      {required this.id,
      required this.organizationId,
      required this.name,
      required this.email,
      required this.role,
      required this.isActive,
      required this.createdAt,
      required this.updatedAt});

  factory _$UserImpl.fromJson(Map<String, dynamic> json) =>
      _$$UserImplFromJson(json);

  @override
  final String id;
  @override
  final String organizationId;
  @override
  final String name;
  @override
  final String email;
  @override
  final String role;
  @override
  final bool isActive;
  @override
  final String createdAt;
  @override
  final String updatedAt;

  @override
  String toString() {
    return 'User(id: $id, organizationId: $organizationId, name: $name, email: $email, role: $role, isActive: $isActive, createdAt: $createdAt, updatedAt: $updatedAt)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$UserImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.organizationId, organizationId) ||
                other.organizationId == organizationId) &&
            (identical(other.name, name) || other.name == name) &&
            (identical(other.email, email) || other.email == email) &&
            (identical(other.role, role) || other.role == role) &&
            (identical(other.isActive, isActive) ||
                other.isActive == isActive) &&
            (identical(other.createdAt, createdAt) ||
                other.createdAt == createdAt) &&
            (identical(other.updatedAt, updatedAt) ||
                other.updatedAt == updatedAt));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(runtimeType, id, organizationId, name, email,
      role, isActive, createdAt, updatedAt);

  /// Create a copy of User
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$UserImplCopyWith<_$UserImpl> get copyWith =>
      __$$UserImplCopyWithImpl<_$UserImpl>(this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$UserImplToJson(
      this,
    );
  }
}

abstract class _User implements User {
  const factory _User(
      {required final String id,
      required final String organizationId,
      required final String name,
      required final String email,
      required final String role,
      required final bool isActive,
      required final String createdAt,
      required final String updatedAt}) = _$UserImpl;

  factory _User.fromJson(Map<String, dynamic> json) = _$UserImpl.fromJson;

  @override
  String get id;
  @override
  String get organizationId;
  @override
  String get name;
  @override
  String get email;
  @override
  String get role;
  @override
  bool get isActive;
  @override
  String get createdAt;
  @override
  String get updatedAt;

  /// Create a copy of User
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$UserImplCopyWith<_$UserImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
