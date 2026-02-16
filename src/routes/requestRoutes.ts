import express from 'express';
import {
  getAssetRequests,
  getAssetRequest,
  getPendingAssetRequests,
  submitAssetRequest,
  reviewAssetRequest,
} from '../controllers/requestController';

const router = express.Router();

// Get all asset requests
router.get('/', getAssetRequests);

// Get pending requests
router.get('/pending', getPendingAssetRequests);

// Get single asset request
router.get('/:id', getAssetRequest);

// Submit new asset request (employee)
router.post('/', submitAssetRequest);

// Review asset request (IT team)
router.put('/:id/review', reviewAssetRequest);

export default router;
