import nodemailer from 'nodemailer';
import { AssetRequest } from '../types';

interface EmailService {
  sendAssetRequestNotification: (request: AssetRequest) => Promise<void>;
}

class EmailServiceImpl implements EmailService {
  private transporter: nodemailer.Transporter | null = null;
  private itEmail: string;
  private hrEmail: string;

  constructor() {
    this.itEmail = process.env.IT_EMAIL || 'it@company.com';
    this.hrEmail = process.env.HR_EMAIL || 'hr@company.com';
    
    // Initialize transporter if SMTP config is available
    if (process.env.SMTP_HOST && process.env.SMTP_USER && process.env.SMTP_PASS) {
      this.transporter = nodemailer.createTransport({
        host: process.env.SMTP_HOST,
        port: parseInt(process.env.SMTP_PORT || '587'),
        secure: process.env.SMTP_SECURE === 'true',
        auth: {
          user: process.env.SMTP_USER,
          pass: process.env.SMTP_PASS,
        },
      });
    } else {
      console.warn('Email service not configured. Email notifications will be logged to console only.');
    }
  }

  async sendAssetRequestNotification(request: AssetRequest): Promise<void> {
    const emailContent = this.formatAssetRequestEmail(request);
    
    if (!this.transporter) {
      // If email is not configured, log to console
      console.log('\n========== EMAIL NOTIFICATION ==========');
      console.log(`To: ${this.itEmail}, ${this.hrEmail}`);
      console.log(`Subject: ${emailContent.subject}`);
      console.log('---');
      console.log(emailContent.text);
      console.log('========================================\n');
      return;
    }

    try {
      await this.transporter.sendMail({
        from: process.env.SMTP_USER,
        to: [this.itEmail, this.hrEmail],
        subject: emailContent.subject,
        text: emailContent.text,
        html: emailContent.html,
      });
      console.log(`Email notification sent to IT and HR for request ${request.id}`);
    } catch (error) {
      console.error('Failed to send email notification:', error);
      // Log to console as fallback
      console.log('\n========== EMAIL NOTIFICATION (FALLBACK) ==========');
      console.log(`To: ${this.itEmail}, ${this.hrEmail}`);
      console.log(`Subject: ${emailContent.subject}`);
      console.log('---');
      console.log(emailContent.text);
      console.log('===================================================\n');
    }
  }

  private formatAssetRequestEmail(request: AssetRequest): { subject: string; text: string; html: string } {
    const subject = `New Asset Request from ${request.employeeName}`;
    
    const text = `
New Asset Request Received

Request ID: ${request.id}
Employee: ${request.employeeName}
Email: ${request.employeeEmail}
Employee ID: ${request.employeeId}

Requested Assets:
${request.requestedAssets.map(asset => `- ${asset}`).join('\n')}

Requirements:
${request.requirements}

Status: ${request.status}
Requested on: ${new Date(request.createdAt).toLocaleString()}

Please review and process this request at your earliest convenience.
    `.trim();

    const html = `
      <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2 style="color: #333;">New Asset Request Received</h2>
        
        <div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
          <p><strong>Request ID:</strong> ${request.id}</p>
          <p><strong>Employee:</strong> ${request.employeeName}</p>
          <p><strong>Email:</strong> ${request.employeeEmail}</p>
          <p><strong>Employee ID:</strong> ${request.employeeId}</p>
        </div>

        <h3 style="color: #555;">Requested Assets:</h3>
        <ul style="background-color: #f9f9f9; padding: 15px 30px; border-radius: 5px;">
          ${request.requestedAssets.map(asset => `<li>${asset}</li>`).join('')}
        </ul>

        <h3 style="color: #555;">Requirements:</h3>
        <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; white-space: pre-wrap;">
          ${request.requirements}
        </div>

        <div style="margin-top: 20px; padding: 10px; background-color: #e3f2fd; border-radius: 5px;">
          <p><strong>Status:</strong> <span style="color: #1976d2;">${request.status.toUpperCase()}</span></p>
          <p><strong>Requested on:</strong> ${new Date(request.createdAt).toLocaleString()}</p>
        </div>

        <p style="margin-top: 20px; color: #666;">
          Please review and process this request at your earliest convenience.
        </p>
      </div>
    `;

    return { subject, text, html };
  }
}

export const emailService = new EmailServiceImpl();
