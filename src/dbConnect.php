<?php

function dbConnect() {
    $host = 'localhost';
    $port = '5432';
    $dbname = 'postgres';
    $username = 'postgres.your-tenant-id';
    $password = 'fbdff60be23cbbb4171c975423a00eb9';

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    try {
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;

    } catch (PDOException $e) {
        echo "Query failed: " . $e->getMessage();
    }
}

function getAssignments() {
    $conn = dbConnect();
    $stmt = $conn->query("SELECT * FROM assignments");
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $assignments;
}

function getClockInTimes() {
    $conn = dbConnect();
    $stmt = $conn->query("SELECT * FROM clock_in_times");
    $clock_in_times = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $clock_in_times;
}

function getGrades() {
    $conn = dbConnect();
    $stmt = $conn->query("SELECT * FROM grades");
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $grades;
}

function getIndividualQuestions() {
    $conn = dbConnect();
    $stmt = $conn->query("SELECT * FROM individual_questions");
    $individual_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $individual_questions;
}

function getLevels() {
    $conn = dbConnect();
    $stmt = $conn->query("SELECT * FROM levels");
    $levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $levels;
}

function getNotifications() {
    $conn = dbConnect();
    $stmt = $conn->query("SELECT * FROM notifications");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $notifications;
}

function getSources() {
    $conn = dbConnect();
    $stmt = $conn->query("SELECT * FROM sources");
    $sources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $sources;
}

function getStudentAssignments() {
    $conn = dbConnect();
    $stmt = $conn->query("SELECT * FROM student_assignments");
    $student_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $student_assignments;
}

function getStudentLevels() {
    $conn = dbConnect();
    $stmt = $conn->query("SELECT * FROM student_levels");
    $student_levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $student_levels;
}

function getStudentSubjects() {
    $conn = dbConnect();
    $stmt = $conn->query("SELECT * FROM student_subjects");
    $student_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $student_subjects;
}

function getStudents() {
    $conn = dbConnect();
    $stmt = $conn->query("SELECT * FROM students");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $students;
}

function getSubjects() {
    $conn = dbConnect();
    $stmt = $conn->query("SELECT * FROM subjects");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $subjects;
}

function getTeachers() {
    $conn = dbConnect();
    $stmt = $conn->query("SELECT * FROM teachers");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $teachers;
}

?>
