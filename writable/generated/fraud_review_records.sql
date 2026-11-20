CREATE TABLE fraud_review_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_id VARCHAR(255) NOT NULL UNIQUE,
        user_id VARCHAR(255) NOT NULL,
        risk_score DECIMAL(10,2) NOT NULL,
        alert_status VARCHAR(20) NOT NULL,
        alert_reason VARCHAR(500)
);
