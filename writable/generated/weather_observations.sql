CREATE TABLE weather_observations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        city VARCHAR(255) NOT NULL,
        temperature DECIMAL(10,2) NOT NULL,
        humidity INT NOT NULL,
        condition VARCHAR(255),
        observed_at DATETIME NOT NULL
);
