import { Request, Response } from 'express';
import {
  getAllAssets,
  getAssetById,
  createAsset,
  updateAsset,
  getAvailableAssetTypes,
} from '../models/assetModel';
import { CreateAssetInput } from '../types';

export const getAssets = async (req: Request, res: Response) => {
  try {
    const assets = getAllAssets();
    res.json(assets);
  } catch (error) {
    console.error('Error fetching assets:', error);
    res.status(500).json({ error: 'Failed to fetch assets' });
  }
};

export const getAsset = async (req: Request, res: Response) => {
  try {
    const id = req.params.id as string;
    const asset = getAssetById(id);
    if (!asset) {
      return res.status(404).json({ error: 'Asset not found' });
    }
    res.json(asset);
  } catch (error) {
    console.error('Error fetching asset:', error);
    res.status(500).json({ error: 'Failed to fetch asset' });
  }
};

export const getAvailableTypes = async (req: Request, res: Response) => {
  try {
    // Returns only asset types without brand/company info for employee view
    const assetTypes = getAvailableAssetTypes();
    res.json({ assetTypes });
  } catch (error) {
    console.error('Error fetching available asset types:', error);
    res.status(500).json({ error: 'Failed to fetch available asset types' });
  }
};

export const addAsset = async (req: Request, res: Response) => {
  try {
    const assetInput: CreateAssetInput = req.body;
    
    // Validate required fields
    if (!assetInput.assetType) {
      return res.status(400).json({ error: 'Asset type is required' });
    }

    // Validate currency if price is provided
    if (assetInput.price && !assetInput.currency) {
      return res.status(400).json({ error: 'Currency is required when price is specified' });
    }

    if (assetInput.currency && !['INR', 'USD'].includes(assetInput.currency)) {
      return res.status(400).json({ error: 'Currency must be either INR or USD' });
    }

    const newAsset = createAsset(assetInput);
    res.status(201).json(newAsset);
  } catch (error) {
    console.error('Error creating asset:', error);
    res.status(500).json({ error: 'Failed to create asset' });
  }
};

export const modifyAsset = async (req: Request, res: Response) => {
  try {
    const id = req.params.id as string;
    const updates = req.body;
    
    const updatedAsset = updateAsset(id, updates);
    if (!updatedAsset) {
      return res.status(404).json({ error: 'Asset not found' });
    }
    
    res.json(updatedAsset);
  } catch (error) {
    console.error('Error updating asset:', error);
    res.status(500).json({ error: 'Failed to update asset' });
  }
};
