# Property Management System - Feature Documentation

## System Overview

The Property Management System is a comprehensive multi-tenant application designed to manage residential properties, guest accommodations, meal services, and facility access. Built with Laravel and Filament, the system provides a robust, role-based access control system to ensure secure operations across different user types.

## Core Technologies

- **Laravel 10**: PHP framework providing the foundation of the application
- **Filament 3**: Admin panel framework for intuitive UI/UX
- **Spatie Laravel Multitenancy**: Enabling isolated tenant environments
- **Spatie Laravel Permission**: Role and permission management
- **Laravel Sanctum**: API authentication
- **Simple QR Code**: QR code generation for access control
- **MySQL/MariaDB**: Database management

## User Management Features

### User Authentication & Authorization

- **Multi-tenant Authentication**: Separate authentication system for each tenant
- **Role-Based Access Control**: Pre-defined roles with specific permissions
- **Permission Management**: Granular control over system features
- **User Profiles**: Customizable user profiles with contact information

### Role System

The system includes predefined roles with specific access levels:

1. **Super Admin**: Complete access to all system features
2. **Admin**: Access to most features except tenant-level system settings
3. **Manager**: Access to operational features like guest management, room assignments, and reports
4. **Staff**: Basic operational access for check-ins, meal tracking, and guest services
5. **Scanner**: Limited access specifically for QR code scanning operations

## Property Management Features

### Room/Apartment Management

- **Room Directory**: Comprehensive listing of all rooms/apartments
- **Room Categories**: Classification by building, floor, and status
- **Room Status Tracking**: Available, occupied, under maintenance
- **Room Assignment**: Link guests to specific rooms
- **Room Details**: Store and display room information including descriptions

### Check-In/Check-Out System

- **Guest Check-In Processing**: Register guests and assign rooms
- **QR Code Generation**: Create unique QR codes for room access
- **Check-Out Management**: Process guest departures
- **Multi-Guest Check-In**: Check in multiple guests simultaneously
- **Stay Duration Tracking**: Monitor arrival and departure dates

## Guest Management Features

### Guest Profiles

- **Guest Directory**: Comprehensive database of all guests
- **Guest Types**: Categorize as resident, staff, or visitor
- **Contact Information**: Store and manage guest details
- **Status Tracking**: Active or inactive status
- **Room Assignments**: Track current room assignments

### Request Management

- **Guest Request System**: Allow guests to submit service requests
- **Request Categories**: Multiple request types (maintenance, consumables, etc.)
- **Request Status Tracking**: Monitor pending, approved, and denied requests
- **Response Management**: Record and track responses to guest requests
- **Request History**: Maintain a log of all guest requests and resolutions

## Meal & Consumable Management

### Meal System

- **Meal Schedule Management**: Define meal times for different days
- **Meal Types**: Configure breakfast, lunch, dinner options
- **Meal Time Ranges**: Set specific time windows for each meal
- **Day-of-Week Configuration**: Different schedules for weekdays and weekends
- **QR Code Scanning**: Track meal access via QR codes

### Meal Records

- **Meal Tracking**: Record when guests access meals
- **Consumption Analytics**: Monitor meal service usage
- **Time-Based Validation**: Ensure meals are accessed during appropriate times
- **Guest-Room Association**: Link meal records to specific guests and rooms

### Consumable Management

- **Consumable Inventory**: Track available items for purchase/request
- **Pricing Management**: Set and update item prices
- **Consumable Categories**: Organize items by type
- **Visibility Control**: Toggle item visibility in the system
- **Consumption Tracking**: Monitor usage of consumables

## Access Control Features

### Scanner Management

- **Scanner Directory**: Track all access scanners in the system
- **Scanner Types**: Door, gate, consumable, or restaurant scanners
- **Location Tracking**: Record scanner locations
- **Status Management**: Active/inactive scanner status
- **QR Code Integration**: Generate scanner-specific QR codes

### Transit Records

- **Entry/Exit Tracking**: Record guest movements in/out of facilities
- **Access Validation**: Verify guest permissions for specific areas
- **Processing Controls**: Allow staff to process check-ins and check-outs
- **Transit History**: Maintain logs of all transit activities
- **Transit Types**: Track different transit categories (check-in, check-out)

## Reporting & Analytics

### Daily Reports

- **Occupancy Reports**: Track room occupancy rates
- **Meal Service Reports**: Monitor meal service usage
- **Guest Activity Reports**: Analyze guest movements and requests
- **Staff Performance Metrics**: Evaluate response times to requests
- **Export Capabilities**: Generate downloadable reports in various formats

## Administrative Features

### Settings Management

- **System Configuration**: Control application-wide settings
- **Tenant Management**: Configure tenant-specific parameters
- **User Administration**: Manage user accounts and permissions
- **Role Configuration**: Create and modify role definitions
- **Permission Assignment**: Assign permissions to roles and users

### Dashboard

- **Overview Metrics**: At-a-glance system performance indicators
- **Recent Activity**: Latest check-ins, requests, and transactions
- **Occupancy Statistics**: Current property occupancy rates
- **Alert System**: Notification of pending requests or issues
- **Quick Access**: Shortcuts to commonly used features

## Security Features

- **Permission-Based UI**: Interface elements disabled/enabled based on user permissions
- **Audit Logging**: Track user actions for security review
- **Data Isolation**: Multi-tenant architecture ensures data separation
- **Authentication Guards**: Specialized guards for different user types
- **Middleware Protections**: Request validation at multiple levels

## Technical Features

- **Multi-Tenancy**: Complete isolation between different property instances
- **Responsive Design**: Mobile-friendly interface for on-the-go management
- **Database Migrations**: Structured database schema management
- **Seeders**: Pre-configured startup data for quick deployment
- **Custom Models**: Specialized data models for property management

## Integration Capabilities

- **API Endpoints**: Potential for integration with other systems
- **External Authentication**: Support for third-party authentication providers
- **Reporting Tools**: Export data to external analytics platforms
- **QR Code System**: Integration with physical access control systems