<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;

class DashboardController extends Controller {
    public function index() {
        $user = Session::get('user');
        if (!$user) {
            header('Location: /login');
            exit;
        }
        $this->view('dashboard.php', ['user' => $user]);
    }

    public function testMail() {
        $user = Session::get('user');
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $to = $user['email'];
        $subject = 'Test Email from Social Network';
        $body = "Hello {$user['name']},\n\n";
        $body .= "This is a test email to verify that Mailtrap integration is working correctly.\n\n";
        $body .= "If you received this email, your email configuration is set up properly!\n\n";
        $body .= "Sent at: " . date('Y-m-d H:i:s') . "\n\n";
        $body .= "Best regards,\nSocial Network Team";

        $result = \App\Core\Mailer::send($to, $subject, $body);

        if ($result) {
            Session::set('success', 'Test email sent successfully! Check your Mailtrap inbox.');
        } else {
            Session::set('error', 'Failed to send test email. Please check your Mailtrap configuration.');
        }

        header('Location: /dashboard');
    }
}
