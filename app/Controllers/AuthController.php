<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\OtpModel;
use CodeIgniter\RESTful\ResourceController;

class AuthController extends ResourceController
{
    public function register()
    {
        $rules = [
            'name'  => 'required',
            'phone' => 'required|exact_length[10]|is_unique[users.phone]',
            'email' => 'required|valid_email|is_unique[users.email]'
        ];

        $input = $this->request->getJSON(true) ?? $this->request->getPost();

        if (!$this->validate($rules)) {
            return $this->respond([
                'status'  => false,
                'message' => $this->validator->getErrors()
            ], 400);
        }

        $userModel = new UserModel();
        $userModel->insert([
            'name'  => $input['name'],
            'phone' => $input['phone'],
            'email' => $input['email']
        ]);

        return $this->sendOtp($input['phone']);
    }

    private function sendOtp($phone)
    {
        //$otp = rand(1000, 9999); // Generate 4-digit OTP
        $otp = 1234; // Static OTP for testing (replace with the above line in production)

        $otpModel = new OtpModel();
        $otpModel->where('phone', $phone)->delete(); // Remove old OTP
        $otpModel->insert(['phone' => $phone, 'otp' => $otp]);

        // TODO: Integrate SMS API to send OTP
        return $this->respond(['status' => "success", 'message' => "OTP sent to $phone"]);
    }

    public function login()
    {
        $rules = ['phone' => 'required|exact_length[10]'];
        $input = $this->request->getJSON(true) ?? $this->request->getPost();

        if (!$this->validate($rules)) {
            return $this->respond([
                'status'  => false,
                'message' => $this->validator->getErrors()
            ], 400);
        }

        if (!isset($input['phone'])) {
            return $this->respond([
                'status'  => false,
                'message' => 'Phone number is required.'
            ], 400);
        }

        $userModel = new UserModel();
        $user = $userModel->where('phone', $input['phone'])->first();

        if (!$user) {
            return $this->respond([
                'status'  => false,
                'message' => 'This number is not registered. Please register.'
            ], 400);
        }

        return $this->sendOtp($input['phone']);
    }



    public function verifyOtp()
    {
        $rules = [
            'phone' => 'required|exact_length[10]',
            'otp'   => 'required|exact_length[4]'
        ];

        $input = $this->request->getJSON(true) ?? $this->request->getPost();

        if (!$this->validate($rules)) {
            return $this->respond([
                'status'  => false,
                'message' => $this->validator->getErrors()
            ], 400);
        }

        if (!isset($input['phone']) || !isset($input['otp'])) {
            return $this->respond([
                'status'  => false,
                'message' => 'Phone and OTP are required.'
            ], 400);
        }

        $otpModel = new OtpModel();
        $otpData = $otpModel->where([
            'phone' => $input['phone'],
            'otp'   => $input['otp']
        ])->first();

        if (!$otpData) {
            return $this->respond([
                'status'  => false,
                'message' => 'Invalid OTP. Please try again.'
            ], 400);
        }

        // OTP is valid - delete from database
        $otpModel->where('phone', $input['phone'])->delete();

        return $this->respond([
            'status'  => true,
            'message' => 'OTP verified successfully!'
        ]);
    }

    public function resendOtp()
    {
        $rules = ['phone' => 'required|exact_length[10]'];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        return $this->sendOtp($this->request->getPost('phone'));
    }
}
