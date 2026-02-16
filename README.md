# Buzznation Assets Management System

A comprehensive web-based asset management system that enables employees to request assets and IT teams to manage inventory and fulfill requests.

## Features

### Employee Features
- **Asset Request Submission**: Employees can request assets by:
  - Viewing available asset types (Laptop, Desktop, Mouse, etc.) in checkboxes
  - Selecting multiple asset types as needed
  - Writing detailed requirements in a text area
  - Submitting requests that automatically notify IT and HR teams via email

**Note**: Employees only see asset types (e.g., "Laptop", "Desktop") without brand or company information for unbiased requests.

### IT Team Features
- **Asset Inventory Management**:
  - Add new assets to the inventory
  - Specify asset details: type, brand, model, serial number
  - Set price with currency selection (INR or USD)
  - Track asset status (available, assigned, maintenance, retired)

- **Request Review & Assignment**:
  - View all employee asset requests
  - Review pending requests with full details
  - Approve, reject, or mark requests as fulfilled
  - Add review notes for each request

### Automated Notifications
- **Email Notifications**: When an employee submits an asset request:
  - IT team receives notification
  - HR team receives notification
  - Email includes all request details and requirements

## Technology Stack

- **Backend**: Node.js with Express and TypeScript
- **Frontend**: HTML, CSS, JavaScript (Vanilla)
- **Data Storage**: JSON file-based storage
- **Email**: Nodemailer (SMTP)

## Installation

### Prerequisites
- Node.js (v14 or higher)
- npm (v6 or higher)

### Setup Steps

1. **Clone the repository**:
   ```bash
   git clone https://github.com/maneesh7787/Buzznation-assets.git
   cd Buzznation-assets
   ```

2. **Install dependencies**:
   ```bash
   npm install
   ```

3. **Configure environment variables**:
   ```bash
   cp .env.example .env
   ```

   Edit `.env` file with your configuration:
   ```env
   PORT=3000
   
   # Email Configuration (for notifications)
   SMTP_HOST=smtp.gmail.com
   SMTP_PORT=587
   SMTP_SECURE=false
   SMTP_USER=your-email@gmail.com
   SMTP_PASS=your-app-password
   
   # Email Recipients
   IT_EMAIL=it-team@company.com
   HR_EMAIL=hr-team@company.com
   
   NODE_ENV=development
   ```

   **Note**: If email is not configured, notifications will be logged to the console.

4. **Build the application**:
   ```bash
   npm run build
   ```

## Running the Application

### Development Mode (with auto-reload):
```bash
npm run dev
```

### Production Mode:
```bash
npm start
```

The application will be available at: `http://localhost:3000`

## Usage Guide

### For Employees

1. Navigate to the **"Employee - Request Assets"** tab
2. Fill in your employee information:
   - Employee ID
   - Full Name
   - Email Address
3. Select the asset types you need from the checkboxes
4. Write detailed requirements describing:
   - Why you need the assets
   - Any specific requirements or preferences
   - Timeline or urgency
5. Click **"Submit Request"**
6. IT and HR teams will be notified automatically

### For IT Team

#### Adding Assets:
1. Navigate to the **"IT - Manage Assets"** tab
2. Fill in the asset details:
   - Asset Type (required) - e.g., Laptop, Desktop, Mouse
   - Brand/Company (optional) - e.g., Dell, HP, Logitech
   - Model (optional) - e.g., XPS 15, ThinkPad T14
   - Serial Number (optional)
   - Price (optional)
   - Currency (required if price is provided) - INR or USD
3. Click **"Add Asset"**
4. The asset will be added to the inventory and visible to employees

#### Reviewing Requests:
1. Navigate to the **"IT - Review Requests"** tab
2. View all asset requests with details:
   - Employee information
   - Requested assets
   - Requirements description
   - Request status
3. For pending requests, you can:
   - **Approve**: Mark the request as approved
   - **Reject**: Reject the request
   - **Mark as Fulfilled**: Indicate assets have been assigned
4. Add your name as reviewer and optional notes

## API Endpoints

### Assets
- `GET /api/assets` - Get all assets
- `GET /api/assets/available-types` - Get available asset types (employee view)
- `GET /api/assets/:id` - Get single asset
- `POST /api/assets` - Add new asset
- `PUT /api/assets/:id` - Update asset

### Requests
- `GET /api/requests` - Get all requests
- `GET /api/requests/pending` - Get pending requests
- `GET /api/requests/:id` - Get single request
- `POST /api/requests` - Submit new request
- `PUT /api/requests/:id/review` - Review a request

### Health Check
- `GET /api/health` - Check API status

## Project Structure

```
Buzznation-assets/
├── src/
│   ├── controllers/      # Request handlers
│   │   ├── assetController.ts
│   │   └── requestController.ts
│   ├── models/          # Data models and storage
│   │   └── assetModel.ts
│   ├── routes/          # API routes
│   │   ├── assetRoutes.ts
│   │   └── requestRoutes.ts
│   ├── services/        # Business logic services
│   │   └── emailService.ts
│   ├── types/           # TypeScript type definitions
│   │   └── index.ts
│   └── index.ts         # Application entry point
├── public/              # Frontend files
│   └── index.html
├── data/                # JSON data storage
│   ├── assets.json
│   └── requests.json
├── dist/                # Compiled JavaScript (generated)
├── .env                 # Environment variables
├── .env.example         # Environment template
├── .gitignore
├── package.json
├── tsconfig.json
└── README.md
```

## Email Configuration

The system supports SMTP-based email notifications. To enable:

1. **Using Gmail**:
   - Enable 2-factor authentication
   - Generate an App Password
   - Use the App Password in `SMTP_PASS`

2. **Using Other SMTP Services**:
   - Update `SMTP_HOST`, `SMTP_PORT`, and credentials accordingly

If email is not configured, notifications will be logged to the console for development purposes.

## Data Storage

The application uses JSON files for data storage:
- `data/assets.json` - Asset inventory
- `data/requests.json` - Asset requests

These files are automatically created on first run and excluded from git.

## Currency Support

When adding assets, IT can specify pricing in two currencies:
- **INR** - Indian Rupee (₹)
- **USD** - US Dollar ($)

Currency selection is required when a price is entered.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

ISC

## Support

For issues or questions, please open an issue on GitHub.
