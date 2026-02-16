import { Request, Response } from 'express';
import {
  getAllAssetRequests,
  getAssetRequestById,
  createAssetRequest,
  updateAssetRequest,
  getPendingRequests,
} from '../models/assetModel';
import { CreateAssetRequest } from '../types';
import { emailService } from '../services/emailService';

export const getAssetRequests = async (req: Request, res: Response) => {
  try {
    const requests = getAllAssetRequests();
    res.json(requests);
  } catch (error) {
    console.error('Error fetching asset requests:', error);
    res.status(500).json({ error: 'Failed to fetch asset requests' });
  }
};

export const getAssetRequest = async (req: Request, res: Response) => {
  try {
    const id = req.params.id as string;
    const request = getAssetRequestById(id);
    if (!request) {
      return res.status(404).json({ error: 'Asset request not found' });
    }
    res.json(request);
  } catch (error) {
    console.error('Error fetching asset request:', error);
    res.status(500).json({ error: 'Failed to fetch asset request' });
  }
};

export const getPendingAssetRequests = async (req: Request, res: Response) => {
  try {
    const requests = getPendingRequests();
    res.json(requests);
  } catch (error) {
    console.error('Error fetching pending requests:', error);
    res.status(500).json({ error: 'Failed to fetch pending requests' });
  }
};

export const submitAssetRequest = async (req: Request, res: Response) => {
  try {
    const requestInput: CreateAssetRequest = req.body;
    
    // Validate required fields
    if (!requestInput.employeeId || !requestInput.employeeName || !requestInput.employeeEmail) {
      return res.status(400).json({ error: 'Employee ID, name, and email are required' });
    }
    
    if (!requestInput.requestedAssets || requestInput.requestedAssets.length === 0) {
      return res.status(400).json({ error: 'At least one asset must be selected' });
    }
    
    if (!requestInput.requirements || requestInput.requirements.trim() === '') {
      return res.status(400).json({ error: 'Requirements description is required' });
    }

    const newRequest = createAssetRequest(requestInput);
    
    // Send email notification to IT and HR
    await emailService.sendAssetRequestNotification(newRequest);
    
    res.status(201).json(newRequest);
  } catch (error) {
    console.error('Error creating asset request:', error);
    res.status(500).json({ error: 'Failed to create asset request' });
  }
};

export const reviewAssetRequest = async (req: Request, res: Response) => {
  try {
    const id = req.params.id as string;
    const { status, reviewedBy, reviewNotes } = req.body;
    
    if (!status || !['approved', 'rejected', 'fulfilled'].includes(status)) {
      return res.status(400).json({ error: 'Valid status is required (approved, rejected, or fulfilled)' });
    }
    
    const updatedRequest = updateAssetRequest(id, {
      status,
      reviewedBy,
      reviewNotes,
    });
    
    if (!updatedRequest) {
      return res.status(404).json({ error: 'Asset request not found' });
    }
    
    res.json(updatedRequest);
  } catch (error) {
    console.error('Error reviewing asset request:', error);
    res.status(500).json({ error: 'Failed to review asset request' });
  }
};
