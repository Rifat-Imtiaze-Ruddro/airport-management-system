<?php
// Start session
session_start();

// Include dependencies
require_once 'includes/config.php';
require_once 'includes/auth.php';

echo "<h1>Login Unit Tests</h1>";

class LoginTester
{
    private $testResults = [];
    
    public function runAllTests()
    {
        $this->testValidLogin();
        $this->testInvalidPassword();
        $this->testNonExistentEmail();
        $this->testEmptyFields();
        $this->testAuthFunctions();
        
        $this->printResults();
    }
    
    private function testValidLogin()
    {
        $_SESSION = [];
        $_POST = [
            'email' => 'admin@airport.com',
            'password' => 'password'
        ];
        
        // Capture output
        ob_start();
        include 'login.php';
        $output = ob_get_clean();
        
        $passed = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
        
        $this->testResults[] = [
            'name' => 'Valid Login',
            'passed' => $passed,
            'message' => $passed ? 'Login successful' : 'Login failed'
        ];
    }
    
    private function testInvalidPassword()
    {
        $_SESSION = [];
        $_POST = [
            'email' => 'admin@airport.com',
            'password' => 'wrongpassword'
        ];
        
        ob_start();
        include 'login.php';
        $output = ob_get_clean();
        
        $passed = !isset($_SESSION['logged_in']) && strpos($output, 'Invalid email or password') !== false;
        
        $this->testResults[] = [
            'name' => 'Invalid Password',
            'passed' => $passed,
            'message' => $passed ? 'Correctly rejected invalid password' : 'Should reject invalid password'
        ];
    }
    
    private function testNonExistentEmail()
    {
        $_SESSION = [];
        $_POST = [
            'email' => 'nonexistent@airport.com',
            'password' => 'password'
        ];
        
        ob_start();
        include 'login.php';
        $output = ob_get_clean();
        
        $passed = !isset($_SESSION['logged_in']) && strpos($output, 'Invalid email or password') !== false;
        
        $this->testResults[] = [
            'name' => 'Non-existent Email',
            'passed' => $passed,
            'message' => $passed ? 'Correctly rejected non-existent email' : 'Should reject non-existent email'
        ];
    }
    
    private function testEmptyFields()
    {
        $_SESSION = [];
        $_POST = [
            'email' => '',
            'password' => ''
        ];
        
        ob_start();
        include 'login.php';
        $output = ob_get_clean();
        
        $passed = !isset($_SESSION['logged_in']) && strpos($output, 'Please enter both email and password') !== false;
        
        $this->testResults[] = [
            'name' => 'Empty Fields',
            'passed' => $passed,
            'message' => $passed ? 'Correctly rejected empty fields' : 'Should reject empty fields'
        ];
    }
    
    private function testAuthFunctions()
    {
        $_SESSION = [
            'logged_in' => true,
            'user_role' => 'airline_staff'
        ];
        
        $passed1 = canAccessFlightManagement() === true;
        $passed2 = canAccessServiceRequests() === true;
        
        $this->testResults[] = [
            'name' => 'Auth Functions - Airline Staff',
            'passed' => $passed1 && $passed2,
            'message' => $passed1 && $passed2 ? 'Correct permissions' : 'Incorrect permissions'
        ];
    }
    
    private function printResults()
    {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Test</th><th>Result</th><th>Message</th></tr>";
        
        foreach ($this->testResults as $result) {
            $color = $result['passed'] ? 'green' : 'red';
            $status = $result['passed'] ? 'PASS' : 'FAIL';
            echo "<tr>";
            echo "<td>{$result['name']}</td>";
            echo "<td style='color: $color; font-weight: bold;'>$status</td>";
            echo "<td>{$result['message']}</td>";
            echo "</tr>";
        }
        
        $total = count($this->testResults);
        $passed = count(array_filter($this->testResults, function($r) { return $r['passed']; }));
        $percentage = round(($passed / $total) * 100, 2);
        
        echo "<tr><td colspan='3'><strong>Results: $passed/$total passed ($percentage%)</strong></td></tr>";
        echo "</table>";
    }
}

// Run tests
$tester = new LoginTester();
$tester->runAllTests();