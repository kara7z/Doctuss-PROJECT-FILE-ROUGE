# Doctuss

Doctuss is a comprehensive healthcare platform that connects patients with qualified medical professionals. It allows users to find doctors, schedule appointments, review their experiences, and manage their health journey easily. For doctors, it provides powerful tools to manage schedules, view appointments, and build their online presence.

## Features

### 🧑‍⚕️ For Patients
- **Find Doctors:** Search for doctors by specialty, location, or name.
- **Book Appointments:** Schedule appointments seamlessly according to doctor's availability.
- **Manage Appointments:** View upcoming appointments, see their status (Pending, Approved, Closed).
- **Reviews:** Leave reviews and ratings for doctors after an appointment.

### 🩺 For Doctors
- **Profile Management:** Create a detailed profile with specialties, experience, and biography.
- **Schedule Management:** Set working dates and recurring weekly schedules.
- **Appointment Handling:** Approve, reject, or propose alternative times for appointment requests.
- **Verification:** Submit verification documents to admins to get a "Verified" badge.

### 🛡️ For Admins
- **Dashboard:** Overview of platform statistics and quick actions.
- **User Management:** View, suspend, and activate user accounts (both doctors and patients).
- **Verification Requests:** Review doctor verification documents and approve/reject requests.
- **Review Moderation:** Oversee platform reviews.

## Tech Stack

- **Frontend:** Vue.js 3, Vite, Vue Router, Vue I18n (English, French, Arabic support)
- **Backend:** Laravel 13, PHP 8.3, Laravel Sanctum for API Authentication
- **Database:** MySQL / SQLite

## Screenshots & Diagrams

### UML Diagram
![UML Diagram](preview/uml%20diagram.png)

### Use Case Diagram
![Use Case Diagram](preview/Use%20case%20diagram.png)

### Search Doctors
![Search](preview/search.png)

### Doctor Profile
![Doctor Profile](preview/doctor_profile.png)

### Admin User Management
![Admin Users](preview/users.png)

## Documentation

- [Cahier de Charge (Project Specifications)](document/Cahier%20de%20Charge%20-%20Projet%20Doctuss.pdf)

## Getting Started

### Prerequisites
- PHP 8.3+
- Node.js 20+
- Composer

### Backend Setup
1. Navigate to the `backend` directory:
   ```bash
   cd backend
   ```
2. Install dependencies:
   ```bash
   composer install
   ```
3. Set up the environment file:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. Run migrations and seed the database:
   ```bash
   php artisan migrate --seed
   ```
5. Start the backend server:
   ```bash
   php artisan serve
   ```

### Frontend Setup
1. Navigate to the `frontend` directory:
   ```bash
   cd frontend
   ```
2. Install dependencies:
   ```bash
   npm install
   ```
3. Run the development server:
   ```bash
   npm run dev
   ```

## License

This project is open-source and available under the [MIT License](LICENSE).
