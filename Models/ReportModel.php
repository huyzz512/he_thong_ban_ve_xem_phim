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

    // 4. Revenue by Time of Day (Khung giờ)
    public function getRevenueByTimeFrame() {
        $query = "
            SELECT 
                CASE 
                    WHEN HOUR(s.start_time) >= 8 AND HOUR(s.start_time) < 12 THEN 'Sáng (08:00 - 12:00)'
                    WHEN HOUR(s.start_time) >= 12 AND HOUR(s.start_time) < 17 THEN 'Chiều (12:00 - 17:00)'
                    WHEN HOUR(s.start_time) >= 17 AND HOUR(s.start_time) < 23 THEN 'Tối (17:00 - 23:00)'
                    ELSE 'Khuya (23:00 - 08:00)'
                END AS time_frame,
                SUM(b.total_amount) AS total_revenue,
                COUNT(b.id) AS booking_count
            FROM bookings b
            JOIN showtimes s ON b.showtime_id = s.id
            WHERE b.status = 'completed'
            GROUP BY time_frame
            ORDER BY total_revenue DESC
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 5. Rich Stats for Dashboard (combining all stats)
    public function getDashboardStats() {
        $stats = [];
        
        // Total Revenue
        $query1 = "SELECT COALESCE(SUM(total_amount), 0) as total FROM bookings WHERE status = 'completed'";
        $stmt1 = $this->conn->prepare($query1);
        $stmt1->execute();
        $stats['total_revenue'] = $stmt1->fetch()['total'];

        // Today Revenue
        $queryToday = "SELECT COALESCE(SUM(total_amount), 0) as total FROM bookings WHERE status = 'completed' AND DATE(created_at) = CURDATE()";
        $stmtToday = $this->conn->prepare($queryToday);
        $stmtToday->execute();
        $stats['revenue_today'] = $stmtToday->fetch()['total'];

        // This Week Revenue
        $queryWeek = "SELECT COALESCE(SUM(total_amount), 0) as total FROM bookings WHERE status = 'completed' AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
        $stmtWeek = $this->conn->prepare($queryWeek);
        $stmtWeek->execute();
        $stats['revenue_week'] = $stmtWeek->fetch()['total'];

        // This Month Revenue
        $queryMonth = "SELECT COALESCE(SUM(total_amount), 0) as total FROM bookings WHERE status = 'completed' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
        $stmtMonth = $this->conn->prepare($queryMonth);
        $stmtMonth->execute();
        $stats['revenue_month'] = $stmtMonth->fetch()['total'];

        // This Year Revenue
        $queryYear = "SELECT COALESCE(SUM(total_amount), 0) as total FROM bookings WHERE status = 'completed' AND YEAR(created_at) = YEAR(CURDATE())";
        $stmtYear = $this->conn->prepare($queryYear);
        $stmtYear->execute();
        $stats['revenue_year'] = $stmtYear->fetch()['total'];

        // Active Movies
        $query2 = "SELECT COUNT(*) as total FROM movies WHERE status = 'showing'";
        $stmt2 = $this->conn->prepare($query2);
        $stmt2->execute();
        $stats['active_movies'] = $stmt2->fetch()['total'];

        // Total Bookings (Giao dịch)
        $query3 = "SELECT COUNT(*) as total FROM bookings";
        $stmt3 = $this->conn->prepare($query3);
        $stmt3->execute();
        $stats['total_bookings'] = $stmt3->fetch()['total'];

        // Completed Bookings
        $queryCompleted = "SELECT COUNT(*) as total FROM bookings WHERE status = 'completed'";
        $stmtCompleted = $this->conn->prepare($queryCompleted);
        $stmtCompleted->execute();
        $stats['completed_bookings'] = $stmtCompleted->fetch()['total'];

        // Cancelled Bookings
        $queryCancelled = "SELECT COUNT(*) as total FROM bookings WHERE status = 'cancelled'";
        $stmtCancelled = $this->conn->prepare($queryCancelled);
        $stmtCancelled->execute();
        $stats['cancelled_bookings'] = $stmtCancelled->fetch()['total'];

        // Pending Bookings
        $queryPending = "SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'";
        $stmtPending = $this->conn->prepare($queryPending);
        $stmtPending->execute();
        $stats['pending_bookings'] = $stmtPending->fetch()['total'];

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

        // Total Tickets Sold (Tổng số vé đã bán)
        $queryTickets = "SELECT COUNT(*) as total FROM booking_details bd JOIN bookings b ON bd.booking_id = b.id WHERE b.status = 'completed'";
        $stmtTickets = $this->conn->prepare($queryTickets);
        $stmtTickets->execute();
        $stats['total_tickets_sold'] = $stmtTickets->fetch()['total'];

        return $stats;
    }
}
?>
