export interface Asset {
  id: string;
  assetType: string; // e.g., "Laptop", "Desktop", "Mouse", "Keyboard", etc.
  brand?: string; // Optional - not shown to employees
  model?: string; // Optional - not shown to employees
  serialNumber?: string;
  price?: number;
  currency?: 'INR' | 'USD';
  status: 'available' | 'assigned' | 'maintenance' | 'retired';
  assignedTo?: string; // Employee ID or name
  purchaseDate?: Date;
  createdAt: Date;
  updatedAt: Date;
}

export interface AssetRequest {
  id: string;
  employeeId: string;
  employeeName: string;
  employeeEmail: string;
  requestedAssets: string[]; // Array of asset types (e.g., ["Laptop", "Mouse"])
  requirements: string; // Text description of requirements
  status: 'pending' | 'approved' | 'rejected' | 'fulfilled';
  reviewedBy?: string;
  reviewNotes?: string;
  createdAt: Date;
  updatedAt: Date;
}

export interface CreateAssetRequest {
  employeeId: string;
  employeeName: string;
  employeeEmail: string;
  requestedAssets: string[];
  requirements: string;
}

export interface CreateAssetInput {
  assetType: string;
  brand?: string;
  model?: string;
  serialNumber?: string;
  price?: number;
  currency?: 'INR' | 'USD';
}

export interface EmailConfig {
  smtpHost: string;
  smtpPort: number;
  smtpSecure: boolean;
  smtpUser: string;
  smtpPass: string;
  itEmail: string;
  hrEmail: string;
}
