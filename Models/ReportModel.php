<?php

class ReportModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. Total Revenue grouped by Cinema
    public function getRevenueByCinema() {
        $query = "
            SELECT c.name AS cinema_name, SUM(b.total_amount) AS total_revenue 
            FROM bookings b
            JOIN showtimes s ON b.showtime_id = s.id
            JOIN rooms r ON s.room_id = r.id
            JOIN cinemas c ON r.cinema_id = c.id
            WHERE b.status = 'completed'
            GROUP BY c.id
            ORDER BY total_revenue DESC
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // 2. Total Revenue and Tickets Sold grouped by Movie
    public function getRevenueAndTicketsByMovie() {
        $query = "
            SELECT m.title AS movie_title, 
                   COUNT(bd.id) AS tickets_sold,
                   SUM(b.total_amount) AS total_revenue 
            FROM bookings b
            JOIN booking_details bd ON b.id = bd.booking_id
            JOIN showtimes s ON b.showtime_id = s.id
            JOIN movies m ON s.movie_id = m.id
            WHERE b.status = 'completed'
            GROUP BY m.id
            ORDER BY total_revenue DESC
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // 3. Revenue filtered by a specific Date Range
    public function getRevenueByDateRange($from_date, $to_date) {
        $query = "
            SELECT DATE(created_at) AS booking_date, SUM(total_amount) AS daily_revenue
            FROM bookings
            WHERE status = 'completed' 
            AND DATE(created_at) >= ? AND DATE(created_at) <= ?
            GROUP BY DATE(created_at)
            ORDER BY booking_date ASC
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$from_date, $to_date]);
        return $stmt->fetchAll();
    }
    public function getDashboardStats() {
        $stats = [];
        
        // Total Revenue
        $query1 = "SELECT COALESCE(SUM(total_amount), 0) as total FROM bookings WHERE status = 'completed'";
        $stmt1 = $this->conn->prepare($query1);
        $stmt1->execute();
        $stats['total_revenue'] = $stmt1->fetch()['total'];

        // Active Movies
        $query2 = "SELECT COUNT(*) as total FROM movies WHERE status = 'showing'";
        $stmt2 = $this->conn->prepare($query2);
        $stmt2->execute();
        $stats['active_movies'] = $stmt2->fetch()['total'];

        // Total Bookings
        $query3 = "SELECT COUNT(*) as total FROM bookings";
        $stmt3 = $this->conn->prepare($query3);
        $stmt3->execute();
        $stats['total_bookings'] = $stmt3->fetch()['total'];

        // Total Cinemas
        $query4 = "SELECT COUNT(*) as total FROM cinemas";
        $stmt4 = $this->conn->prepare($query4);
        $stmt4->execute();
        $stats['total_cinemas'] = $stmt4->fetch()['total'];

        // Total Showtimes
        $query5 = "SELECT COUNT(*) as total FROM showtimes";
        $stmt5 = $this->conn->prepare($query5);
        $stmt5->execute();
        $stats['total_showtimes'] = $stmt5->fetch()['total'];

        return $stats;
    }
}
?>
