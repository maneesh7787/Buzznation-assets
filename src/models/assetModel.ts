import { Asset, AssetRequest, CreateAssetInput, CreateAssetRequest } from '../types';
import fs from 'fs';
import path from 'path';

const ASSETS_FILE = path.join(__dirname, '../../data/assets.json');
const REQUESTS_FILE = path.join(__dirname, '../../data/requests.json');

// Ensure data directory and files exist
const initDataFiles = () => {
  const dataDir = path.join(__dirname, '../../data');
  if (!fs.existsSync(dataDir)) {
    fs.mkdirSync(dataDir, { recursive: true });
  }
  if (!fs.existsSync(ASSETS_FILE)) {
    fs.writeFileSync(ASSETS_FILE, JSON.stringify([], null, 2));
  }
  if (!fs.existsSync(REQUESTS_FILE)) {
    fs.writeFileSync(REQUESTS_FILE, JSON.stringify([], null, 2));
  }
};

// Asset operations
export const getAllAssets = (): Asset[] => {
  initDataFiles();
  const data = fs.readFileSync(ASSETS_FILE, 'utf-8');
  return JSON.parse(data);
};

export const getAssetById = (id: string): Asset | undefined => {
  const assets = getAllAssets();
  return assets.find(asset => asset.id === id);
};

export const getAvailableAssetTypes = (): string[] => {
  const assets = getAllAssets();
  // Get unique asset types from available assets
  const assetTypes = new Set<string>();
  assets.forEach(asset => {
    if (asset.status === 'available') {
      assetTypes.add(asset.assetType);
    }
  });
  return Array.from(assetTypes);
};

export const createAsset = (input: CreateAssetInput): Asset => {
  const assets = getAllAssets();
  const newAsset: Asset = {
    id: `asset_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
    ...input,
    status: 'available',
    createdAt: new Date(),
    updatedAt: new Date(),
  };
  assets.push(newAsset);
  fs.writeFileSync(ASSETS_FILE, JSON.stringify(assets, null, 2));
  return newAsset;
};

export const updateAsset = (id: string, updates: Partial<Asset>): Asset | null => {
  const assets = getAllAssets();
  const index = assets.findIndex(asset => asset.id === id);
  if (index === -1) return null;
  
  assets[index] = {
    ...assets[index],
    ...updates,
    updatedAt: new Date(),
  };
  fs.writeFileSync(ASSETS_FILE, JSON.stringify(assets, null, 2));
  return assets[index];
};

// Asset Request operations
export const getAllAssetRequests = (): AssetRequest[] => {
  initDataFiles();
  const data = fs.readFileSync(REQUESTS_FILE, 'utf-8');
  return JSON.parse(data);
};

export const getAssetRequestById = (id: string): AssetRequest | undefined => {
  const requests = getAllAssetRequests();
  return requests.find(request => request.id === id);
};

export const createAssetRequest = (input: CreateAssetRequest): AssetRequest => {
  const requests = getAllAssetRequests();
  const newRequest: AssetRequest = {
    id: `req_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
    ...input,
    status: 'pending',
    createdAt: new Date(),
    updatedAt: new Date(),
  };
  requests.push(newRequest);
  fs.writeFileSync(REQUESTS_FILE, JSON.stringify(requests, null, 2));
  return newRequest;
};

export const updateAssetRequest = (id: string, updates: Partial<AssetRequest>): AssetRequest | null => {
  const requests = getAllAssetRequests();
  const index = requests.findIndex(request => request.id === id);
  if (index === -1) return null;
  
  requests[index] = {
    ...requests[index],
    ...updates,
    updatedAt: new Date(),
  };
  fs.writeFileSync(REQUESTS_FILE, JSON.stringify(requests, null, 2));
  return requests[index];
};

export const getPendingRequests = (): AssetRequest[] => {
  const requests = getAllAssetRequests();
  return requests.filter(request => request.status === 'pending');
};
