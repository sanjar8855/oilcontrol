# 📱 OilControl Flutter Mobile App - Complete Roadmap

Complete development roadmap for building the OilControl mobile application using Flutter.

---

## 📋 Project Overview

**App Name:** OilControl Mobile
**Platform:** iOS & Android (Flutter)
**Backend:** Laravel API (already built)
**State Management:** Provider / Riverpod
**Target Users:** Workshop owners and staff

---

## 🎯 Core Features

### Phase 1: Authentication & Setup
- ✅ Splash Screen
- ✅ Onboarding (optional)
- ✅ Login Screen
- ✅ Register Screen
- ✅ Token Management (Secure Storage)

### Phase 2: Dashboard
- ✅ Statistics Cards
- ✅ Recent Clients List
- ✅ Workshop Info
- ✅ Subscription Status

### Phase 3: Clients Management
- ✅ List All Clients
- ✅ Search & Filter Clients
- ✅ Add New Client
- ✅ View Client Details
- ✅ Edit Client
- ✅ Delete Client (with confirmation)

### Phase 4: Vehicles Management
- ✅ List All Vehicles
- ✅ Add New Vehicle
- ✅ View Vehicle Details (with service history)
- ✅ Edit Vehicle
- ✅ Delete Vehicle

### Phase 5: Service Logs Management
- ✅ List All Service Logs
- ✅ Add New Service Log
- ✅ View Service Details
- ✅ Edit Service Log
- ✅ Automatic Reminder Creation

### Phase 6: Additional Features
- ✅ Settings Screen
- ✅ Profile Management
- ✅ Notifications (Push - optional)
- ✅ Offline Support (optional)
- ✅ Dark Mode (optional)

---

## 🛠️ Technology Stack

### Required Packages

```yaml
dependencies:
  flutter:
    sdk: flutter

  # State Management
  provider: ^6.1.0         # or riverpod

  # HTTP Requests
  http: ^1.1.0
  dio: ^5.4.0              # Alternative to http (better for API)

  # Secure Storage (for tokens)
  flutter_secure_storage: ^9.0.0

  # Local Storage
  shared_preferences: ^2.2.0

  # JSON Serialization
  json_annotation: ^4.8.0

  # Navigation
  go_router: ^13.0.0       # or use Navigator 2.0

  # UI Components
  flutter_svg: ^2.0.9
  cached_network_image: ^3.3.0
  shimmer: ^3.0.0          # Loading skeleton

  # Forms & Validation
  flutter_form_builder: ^9.1.1
  form_builder_validators: ^9.1.0

  # Date/Time
  intl: ^0.19.0

  # Icons
  font_awesome_flutter: ^10.6.0

  # Toasts/Snackbars
  fluttertoast: ^8.2.4

  # Pull to Refresh
  pull_to_refresh: ^2.0.0

  # Image Picker (optional)
  image_picker: ^1.0.7

  # URL Launcher
  url_launcher: ^6.2.4

dev_dependencies:
  flutter_test:
    sdk: flutter

  # Code Generation
  build_runner: ^2.4.7
  json_serializable: ^6.7.1

  # Linting
  flutter_lints: ^3.0.1
```

---

## 📁 Project Structure

```
lib/
├── main.dart
├── app.dart
│
├── config/
│   ├── app_config.dart         # API base URL, app settings
│   ├── theme.dart              # App theme (colors, text styles)
│   └── routes.dart             # Route definitions
│
├── core/
│   ├── constants/
│   │   ├── api_constants.dart  # API endpoints
│   │   ├── app_constants.dart  # App-wide constants
│   │   └── storage_keys.dart   # Storage keys
│   │
│   ├── utils/
│   │   ├── validators.dart     # Form validators
│   │   ├── date_formatter.dart # Date formatting
│   │   └── currency_formatter.dart
│   │
│   ├── errors/
│   │   └── api_exception.dart  # Custom exceptions
│   │
│   └── widgets/
│       ├── custom_button.dart
│       ├── custom_text_field.dart
│       ├── loading_indicator.dart
│       └── empty_state.dart
│
├── data/
│   ├── models/
│   │   ├── user_model.dart
│   │   ├── workshop_model.dart
│   │   ├── client_model.dart
│   │   ├── vehicle_model.dart
│   │   ├── service_log_model.dart
│   │   └── reminder_model.dart
│   │
│   ├── repositories/
│   │   ├── auth_repository.dart
│   │   ├── client_repository.dart
│   │   ├── vehicle_repository.dart
│   │   └── service_log_repository.dart
│   │
│   └── services/
│       ├── api_service.dart    # HTTP client
│       ├── storage_service.dart # Secure storage
│       └── auth_service.dart   # Authentication logic
│
├── presentation/
│   ├── screens/
│   │   ├── splash/
│   │   │   └── splash_screen.dart
│   │   │
│   │   ├── auth/
│   │   │   ├── login_screen.dart
│   │   │   └── register_screen.dart
│   │   │
│   │   ├── dashboard/
│   │   │   └── dashboard_screen.dart
│   │   │
│   │   ├── clients/
│   │   │   ├── clients_list_screen.dart
│   │   │   ├── client_detail_screen.dart
│   │   │   ├── add_client_screen.dart
│   │   │   └── edit_client_screen.dart
│   │   │
│   │   ├── vehicles/
│   │   │   ├── vehicles_list_screen.dart
│   │   │   ├── vehicle_detail_screen.dart
│   │   │   ├── add_vehicle_screen.dart
│   │   │   └── edit_vehicle_screen.dart
│   │   │
│   │   ├── service_logs/
│   │   │   ├── service_logs_list_screen.dart
│   │   │   ├── service_log_detail_screen.dart
│   │   │   ├── add_service_log_screen.dart
│   │   │   └── edit_service_log_screen.dart
│   │   │
│   │   └── settings/
│   │       └── settings_screen.dart
│   │
│   ├── widgets/
│   │   ├── client_card.dart
│   │   ├── vehicle_card.dart
│   │   ├── service_log_card.dart
│   │   └── stat_card.dart
│   │
│   └── providers/
│       ├── auth_provider.dart
│       ├── dashboard_provider.dart
│       ├── clients_provider.dart
│       ├── vehicles_provider.dart
│       └── service_logs_provider.dart
│
└── l10n/                       # Localization (optional)
    ├── app_en.arb
    └── app_uz.arb
```

---

## 🚀 Development Phases

### **Phase 1: Project Setup (2-3 days)**

#### Step 1: Create Flutter Project
```bash
flutter create oilcontrol_mobile
cd oilcontrol_mobile
```

#### Step 2: Add Dependencies
Add all required packages to `pubspec.yaml`.

#### Step 3: Configure App
- Set app name, package name
- Configure Android & iOS
- Add app icons & splash screen

#### Step 4: Setup Project Structure
Create folders as per structure above.

#### Step 5: Create Base Configuration
```dart
// lib/config/app_config.dart
class AppConfig {
  static const String baseUrl = 'http://your-domain.com/api';
  static const String appName = 'OilControl';
  static const Duration apiTimeout = Duration(seconds: 30);
}
```

```dart
// lib/config/theme.dart
class AppTheme {
  static ThemeData lightTheme = ThemeData(
    primaryColor: Color(0xFF2196F3),
    colorScheme: ColorScheme.fromSeed(seedColor: Color(0xFF2196F3)),
    useMaterial3: true,
  );
}
```

---

### **Phase 2: API Integration & Models (3-4 days)**

#### Step 1: Create Models

```dart
// lib/data/models/user_model.dart
class UserModel {
  final int id;
  final String name;
  final String email;

  UserModel({
    required this.id,
    required this.name,
    required this.email,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) => UserModel(
    id: json['id'],
    name: json['name'],
    email: json['email'],
  );

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'email': email,
  };
}
```

```dart
// lib/data/models/client_model.dart
class ClientModel {
  final int id;
  final int workshopId;
  final String name;
  final String phone;
  final String? telegramId;
  final String? email;
  final String? notes;
  final int? vehiclesCount;

  ClientModel({
    required this.id,
    required this.workshopId,
    required this.name,
    required this.phone,
    this.telegramId,
    this.email,
    this.notes,
    this.vehiclesCount,
  });

  factory ClientModel.fromJson(Map<String, dynamic> json) => ClientModel(
    id: json['id'],
    workshopId: json['workshop_id'],
    name: json['name'],
    phone: json['phone'],
    telegramId: json['telegram_id'],
    email: json['email'],
    notes: json['notes'],
    vehiclesCount: json['vehicles_count'],
  );

  Map<String, dynamic> toJson() => {
    'name': name,
    'phone': phone,
    'telegram_id': telegramId,
    'email': email,
    'notes': notes,
  };
}
```

Create similar models for:
- `VehicleModel`
- `ServiceLogModel`
- `ReminderModel`
- `WorkshopModel`

#### Step 2: Create API Service

```dart
// lib/data/services/api_service.dart
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ApiService {
  late Dio _dio;
  final FlutterSecureStorage _storage = FlutterSecureStorage();

  ApiService() {
    _dio = Dio(BaseOptions(
      baseUrl: AppConfig.baseUrl,
      connectTimeout: AppConfig.apiTimeout,
      receiveTimeout: AppConfig.apiTimeout,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ));

    // Add interceptor for token
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _storage.read(key: 'auth_token');
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
      onError: (error, handler) {
        // Handle errors globally
        return handler.next(error);
      },
    ));
  }

  Future<Response> get(String path) async {
    return await _dio.get(path);
  }

  Future<Response> post(String path, dynamic data) async {
    return await _dio.post(path, data: data);
  }

  Future<Response> put(String path, dynamic data) async {
    return await _dio.put(path, data: data);
  }

  Future<Response> delete(String path) async {
    return await _dio.delete(path);
  }
}
```

#### Step 3: Create Repositories

```dart
// lib/data/repositories/auth_repository.dart
class AuthRepository {
  final ApiService _apiService = ApiService();
  final FlutterSecureStorage _storage = FlutterSecureStorage();

  Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await _apiService.post('/login', {
        'email': email,
        'password': password,
      });

      final token = response.data['token'];
      await _storage.write(key: 'auth_token', value: token);

      return response.data;
    } catch (e) {
      throw Exception('Login failed: $e');
    }
  }

  Future<Map<String, dynamic>> register(Map<String, dynamic> data) async {
    try {
      final response = await _apiService.post('/register', data);

      final token = response.data['token'];
      await _storage.write(key: 'auth_token', value: token);

      return response.data;
    } catch (e) {
      throw Exception('Registration failed: $e');
    }
  }

  Future<void> logout() async {
    try {
      await _apiService.post('/logout', {});
      await _storage.delete(key: 'auth_token');
    } catch (e) {
      throw Exception('Logout failed: $e');
    }
  }

  Future<bool> isLoggedIn() async {
    final token = await _storage.read(key: 'auth_token');
    return token != null;
  }
}
```

```dart
// lib/data/repositories/client_repository.dart
class ClientRepository {
  final ApiService _apiService = ApiService();

  Future<List<ClientModel>> getClients({int page = 1}) async {
    try {
      final response = await _apiService.get('/clients?page=$page');
      final List data = response.data['data'];
      return data.map((json) => ClientModel.fromJson(json)).toList();
    } catch (e) {
      throw Exception('Failed to load clients: $e');
    }
  }

  Future<ClientModel> getClient(int id) async {
    try {
      final response = await _apiService.get('/clients/$id');
      return ClientModel.fromJson(response.data['client']);
    } catch (e) {
      throw Exception('Failed to load client: $e');
    }
  }

  Future<ClientModel> createClient(Map<String, dynamic> data) async {
    try {
      final response = await _apiService.post('/clients', data);
      return ClientModel.fromJson(response.data['client']);
    } catch (e) {
      throw Exception('Failed to create client: $e');
    }
  }

  Future<ClientModel> updateClient(int id, Map<String, dynamic> data) async {
    try {
      final response = await _apiService.put('/clients/$id', data);
      return ClientModel.fromJson(response.data['client']);
    } catch (e) {
      throw Exception('Failed to update client: $e');
    }
  }

  Future<void> deleteClient(int id) async {
    try {
      await _apiService.delete('/clients/$id');
    } catch (e) {
      throw Exception('Failed to delete client: $e');
    }
  }
}
```

Create similar repositories for Vehicles, ServiceLogs, Dashboard.

---

### **Phase 3: State Management with Provider (3-4 days)**

#### Step 1: Create Providers

```dart
// lib/presentation/providers/auth_provider.dart
import 'package:flutter/foundation.dart';

class AuthProvider with ChangeNotifier {
  final AuthRepository _authRepository = AuthRepository();

  bool _isLoading = false;
  bool _isAuthenticated = false;
  UserModel? _user;
  WorkshopModel? _workshop;
  String? _errorMessage;

  bool get isLoading => _isLoading;
  bool get isAuthenticated => _isAuthenticated;
  UserModel? get user => _user;
  WorkshopModel? get workshop => _workshop;
  String? get errorMessage => _errorMessage;

  Future<bool> login(String email, String password) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _authRepository.login(email, password);
      _user = UserModel.fromJson(result['user']);
      _workshop = WorkshopModel.fromJson(result['workshop']);
      _isAuthenticated = true;
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> register(Map<String, dynamic> data) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _authRepository.register(data);
      _user = UserModel.fromJson(result['user']);
      _workshop = WorkshopModel.fromJson(result['workshop']);
      _isAuthenticated = true;
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> logout() async {
    await _authRepository.logout();
    _user = null;
    _workshop = null;
    _isAuthenticated = false;
    notifyListeners();
  }

  Future<void> checkAuthStatus() async {
    _isAuthenticated = await _authRepository.isLoggedIn();
    notifyListeners();
  }
}
```

```dart
// lib/presentation/providers/clients_provider.dart
class ClientsProvider with ChangeNotifier {
  final ClientRepository _clientRepository = ClientRepository();

  List<ClientModel> _clients = [];
  bool _isLoading = false;
  String? _errorMessage;

  List<ClientModel> get clients => _clients;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  Future<void> fetchClients() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _clients = await _clientRepository.getClients();
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> addClient(Map<String, dynamic> data) async {
    try {
      final newClient = await _clientRepository.createClient(data);
      _clients.insert(0, newClient);
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
      return false;
    }
  }

  Future<bool> updateClient(int id, Map<String, dynamic> data) async {
    try {
      final updatedClient = await _clientRepository.updateClient(id, data);
      final index = _clients.indexWhere((c) => c.id == id);
      if (index != -1) {
        _clients[index] = updatedClient;
        notifyListeners();
      }
      return true;
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
      return false;
    }
  }

  Future<bool> deleteClient(int id) async {
    try {
      await _clientRepository.deleteClient(id);
      _clients.removeWhere((c) => c.id == id);
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
      return false;
    }
  }
}
```

Create similar providers for Vehicles, ServiceLogs, Dashboard.

---

### **Phase 4: UI Screens (7-10 days)**

#### 1. Splash Screen
```dart
// lib/presentation/screens/splash/splash_screen.dart
class SplashScreen extends StatefulWidget {
  @override
  _SplashScreenState createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _checkAuth();
  }

  Future<void> _checkAuth() async {
    await Future.delayed(Duration(seconds: 2));

    final authProvider = context.read<AuthProvider>();
    await authProvider.checkAuthStatus();

    if (authProvider.isAuthenticated) {
      Navigator.pushReplacementNamed(context, '/dashboard');
    } else {
      Navigator.pushReplacementNamed(context, '/login');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.car_repair, size: 100, color: Colors.blue),
            SizedBox(height: 20),
            Text('OilControl', style: TextStyle(fontSize: 32, fontWeight: FontWeight.bold)),
            SizedBox(height: 20),
            CircularProgressIndicator(),
          ],
        ),
      ),
    );
  }
}
```

#### 2. Login Screen
```dart
// lib/presentation/screens/auth/login_screen.dart
class LoginScreen extends StatefulWidget {
  @override
  _LoginScreenState createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Login')),
      body: Padding(
        padding: EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Logo
              Icon(Icons.car_repair, size: 80, color: Colors.blue),
              SizedBox(height: 30),

              // Email field
              TextFormField(
                controller: _emailController,
                decoration: InputDecoration(
                  labelText: 'Email',
                  border: OutlineInputBorder(),
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please enter email';
                  }
                  return null;
                },
              ),
              SizedBox(height: 16),

              // Password field
              TextFormField(
                controller: _passwordController,
                decoration: InputDecoration(
                  labelText: 'Password',
                  border: OutlineInputBorder(),
                ),
                obscureText: true,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please enter password';
                  }
                  return null;
                },
              ),
              SizedBox(height: 24),

              // Login button
              Consumer<AuthProvider>(
                builder: (context, authProvider, child) {
                  if (authProvider.isLoading) {
                    return CircularProgressIndicator();
                  }

                  return ElevatedButton(
                    onPressed: () async {
                      if (_formKey.currentState!.validate()) {
                        final success = await authProvider.login(
                          _emailController.text,
                          _passwordController.text,
                        );

                        if (success) {
                          Navigator.pushReplacementNamed(context, '/dashboard');
                        } else {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text(authProvider.errorMessage ?? 'Login failed')),
                          );
                        }
                      }
                    },
                    child: Text('Login'),
                    style: ElevatedButton.styleFrom(
                      minimumSize: Size(double.infinity, 50),
                    ),
                  );
                },
              ),

              SizedBox(height: 16),

              // Register link
              TextButton(
                onPressed: () {
                  Navigator.pushNamed(context, '/register');
                },
                child: Text('Don\'t have an account? Register'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
```

#### 3. Dashboard Screen
```dart
// lib/presentation/screens/dashboard/dashboard_screen.dart
class DashboardScreen extends StatefulWidget {
  @override
  _DashboardScreenState createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() {
      context.read<DashboardProvider>().fetchDashboard();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Dashboard'),
        actions: [
          IconButton(
            icon: Icon(Icons.settings),
            onPressed: () {
              Navigator.pushNamed(context, '/settings');
            },
          ),
        ],
      ),
      body: Consumer<DashboardProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading) {
            return Center(child: CircularProgressIndicator());
          }

          if (provider.errorMessage != null) {
            return Center(child: Text(provider.errorMessage!));
          }

          final stats = provider.stats;

          return RefreshIndicator(
            onRefresh: () => provider.fetchDashboard(),
            child: ListView(
              padding: EdgeInsets.all(16),
              children: [
                // Workshop info
                Card(
                  child: Padding(
                    padding: EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(provider.workshop?.name ?? '',
                          style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                        SizedBox(height: 8),
                        Text('Subscription: ${provider.workshop?.subscriptionPlan}'),
                        Text('Days remaining: ${stats?['days_remaining']}'),
                      ],
                    ),
                  ),
                ),

                SizedBox(height: 16),

                // Statistics
                GridView.count(
                  crossAxisCount: 2,
                  shrinkWrap: true,
                  physics: NeverScrollableScrollPhysics(),
                  mainAxisSpacing: 16,
                  crossAxisSpacing: 16,
                  children: [
                    _buildStatCard('Clients', stats?['total_clients'] ?? 0, Icons.people),
                    _buildStatCard('Vehicles', stats?['total_vehicles'] ?? 0, Icons.directions_car),
                    _buildStatCard('Service Logs', stats?['total_service_logs'] ?? 0, Icons.build),
                    _buildStatCard('Reminders', stats?['pending_reminders'] ?? 0, Icons.notifications),
                  ],
                ),

                SizedBox(height: 16),

                // Recent clients
                Text('Recent Clients', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                SizedBox(height: 8),
                ...provider.recentClients.map((client) {
                  return Card(
                    child: ListTile(
                      leading: CircleAvatar(child: Icon(Icons.person)),
                      title: Text(client.name),
                      subtitle: Text(client.phone),
                      trailing: Text('${client.vehiclesCount ?? 0} vehicles'),
                      onTap: () {
                        Navigator.pushNamed(context, '/clients/${client.id}');
                      },
                    ),
                  );
                }).toList(),
              ],
            ),
          );
        },
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: 0,
        items: [
          BottomNavigationBarItem(icon: Icon(Icons.dashboard), label: 'Dashboard'),
          BottomNavigationBarItem(icon: Icon(Icons.people), label: 'Clients'),
          BottomNavigationBarItem(icon: Icon(Icons.directions_car), label: 'Vehicles'),
          BottomNavigationBarItem(icon: Icon(Icons.build), label: 'Services'),
        ],
        onTap: (index) {
          // Navigate to different screens
        },
      ),
    );
  }

  Widget _buildStatCard(String title, int value, IconData icon) {
    return Card(
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 40, color: Colors.blue),
            SizedBox(height: 8),
            Text('$value', style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
            Text(title, style: TextStyle(color: Colors.grey)),
          ],
        ),
      ),
    );
  }
}
```

#### 4. Clients List Screen
```dart
// lib/presentation/screens/clients/clients_list_screen.dart
class ClientsListScreen extends StatefulWidget {
  @override
  _ClientsListScreenState createState() => _ClientsListScreenState();
}

class _ClientsListScreenState extends State<ClientsListScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() {
      context.read<ClientsProvider>().fetchClients();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Clients'),
        actions: [
          IconButton(
            icon: Icon(Icons.search),
            onPressed: () {
              // Implement search
            },
          ),
        ],
      ),
      body: Consumer<ClientsProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading) {
            return Center(child: CircularProgressIndicator());
          }

          if (provider.clients.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.people_outline, size: 80, color: Colors.grey),
                  SizedBox(height: 16),
                  Text('No clients yet', style: TextStyle(color: Colors.grey)),
                  SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () {
                      Navigator.pushNamed(context, '/clients/add');
                    },
                    child: Text('Add First Client'),
                  ),
                ],
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () => provider.fetchClients(),
            child: ListView.builder(
              itemCount: provider.clients.length,
              itemBuilder: (context, index) {
                final client = provider.clients[index];
                return Card(
                  margin: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: ListTile(
                    leading: CircleAvatar(
                      child: Text(client.name[0].toUpperCase()),
                    ),
                    title: Text(client.name),
                    subtitle: Text(client.phone),
                    trailing: Text('${client.vehiclesCount ?? 0} vehicles'),
                    onTap: () {
                      Navigator.pushNamed(context, '/clients/${client.id}');
                    },
                  ),
                );
              },
            ),
          );
        },
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          Navigator.pushNamed(context, '/clients/add');
        },
        child: Icon(Icons.add),
      ),
    );
  }
}
```

Continue creating screens for:
- Add/Edit Client
- Vehicles List/Detail/Add/Edit
- Service Logs List/Detail/Add/Edit
- Settings

---

### **Phase 5: Testing & Debugging (2-3 days)**

1. **Unit Tests:**
   - Test models
   - Test repositories
   - Test providers

2. **Widget Tests:**
   - Test UI components
   - Test forms

3. **Integration Tests:**
   - Test complete flows
   - Test API integration

---

### **Phase 6: Build & Release (1-2 days)**

1. **Android:**
   - Configure signing
   - Build APK/AAB
   - Test on real devices
   - Upload to Play Store

2. **iOS:**
   - Configure signing
   - Build IPA
   - Test on real devices
   - Upload to App Store

---

## 📝 Additional Recommendations

### 1. Error Handling
- Implement global error handler
- Show user-friendly error messages
- Log errors for debugging

### 2. Loading States
- Show loading indicators
- Implement skeleton screens
- Use shimmer effect

### 3. Offline Support
- Cache data locally
- Queue operations when offline
- Sync when online

### 4. Push Notifications (Optional)
- Firebase Cloud Messaging
- Show reminder notifications
- Handle notification taps

### 5. Internationalization
- Support Uzbek and English
- Use l10n package
- RTL support

---

## 🎓 Learning Resources

**Flutter Documentation:**
- https://flutter.dev/docs

**State Management:**
- Provider: https://pub.dev/packages/provider
- Riverpod: https://riverpod.dev

**API Integration:**
- Dio: https://pub.dev/packages/dio
- HTTP: https://pub.dev/packages/http

**UI Inspiration:**
- https://dribbble.com
- https://www.uplabs.com

---

## ⏱️ Estimated Timeline

| Phase | Duration | Description |
|-------|----------|-------------|
| 1. Setup | 2-3 days | Project setup, dependencies |
| 2. API & Models | 3-4 days | Models, repositories, services |
| 3. State Management | 3-4 days | Providers setup |
| 4. UI Screens | 7-10 days | All screens and widgets |
| 5. Testing | 2-3 days | Unit, widget, integration tests |
| 6. Build & Release | 1-2 days | Build and deploy |
| **Total** | **18-26 days** | Approximately 3-4 weeks |

---

## ✅ Checklist

**Setup:**
- [ ] Create Flutter project
- [ ] Add dependencies
- [ ] Configure app icons & splash
- [ ] Setup project structure

**Backend Integration:**
- [ ] Create models
- [ ] Create API service
- [ ] Create repositories
- [ ] Test API calls

**State Management:**
- [ ] Setup Provider
- [ ] Create all providers
- [ ] Test state management

**UI Development:**
- [ ] Splash screen
- [ ] Login/Register
- [ ] Dashboard
- [ ] Clients screens
- [ ] Vehicles screens
- [ ] Service logs screens
- [ ] Settings screen

**Testing:**
- [ ] Unit tests
- [ ] Widget tests
- [ ] Integration tests
- [ ] Manual testing

**Release:**
- [ ] Android build
- [ ] iOS build
- [ ] Play Store upload
- [ ] App Store upload

---

**Ready to start Flutter development! 🚀**

**Next chat:** Flutter implementation with complete code for all screens!
