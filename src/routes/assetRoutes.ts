import express from 'express';
import {
  getAssets,
  getAsset,
  getAvailableTypes,
  addAsset,
  modifyAsset,
} from '../controllers/assetController';

const router = express.Router();

// Get all assets
router.get('/', getAssets);

// Get available asset types (for employee view - no brand info)
router.get('/available-types', getAvailableTypes);

// Get single asset
router.get('/:id', getAsset);

// Create new asset
router.post('/', addAsset);

// Update asset
router.put('/:id', modifyAsset);

export default router;
