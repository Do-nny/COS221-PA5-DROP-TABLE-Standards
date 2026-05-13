--  Conventions
--    snake_case identifiers
--    INT UNSIGNED AUTO_INCREMENT for keys
--    DECIMAL for money
--    ENUM for fixed status sets
--    ON DELETE CASCADE on weak-entitys


-- Drop in reverse dependency order
DROP TABLE IF EXISTS restaurant;
DROP TABLE IF EXISTS tourist_attraction;
DROP TABLE IF EXISTS accommodation;
DROP TABLE IF EXISTS flight;
DROP TABLE IF EXISTS includes;
DROP TABLE IF EXISTS group_trip;
DROP TABLE IF EXISTS travel_package_images;
DROP TABLE IF EXISTS itinerary_day;
DROP TABLE IF EXISTS package_review;
DROP TABLE IF EXISTS booking;
DROP TABLE IF EXISTS travel_package;
DROP TABLE IF EXISTS agency_phone_number;
DROP TABLE IF EXISTS agency_review;
DROP TABLE IF EXISTS travel_item;
DROP TABLE IF EXISTS traveler;
DROP TABLE IF EXISTS travel_agency;
DROP TABLE IF EXISTS destination;


--  Independent tables (no FKs)

CREATE TABLE destination (
    destination_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    city            VARCHAR(100) NOT NULL,
    country         VARCHAR(100) NOT NULL
);

CREATE TABLE travel_agency (
    user_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone_number    VARCHAR(20)  NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    agency_name     VARCHAR(150) NOT NULL
);

CREATE TABLE traveler (
    user_id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone_number      VARCHAR(20)  NOT NULL,
    email             VARCHAR(255) NOT NULL UNIQUE,
    password_hash     VARCHAR(255) NOT NULL,
    id_number         VARCHAR(20)  NOT NULL UNIQUE,
    residing_country  VARCHAR(100) NOT NULL,
    date_of_birth     DATE         NOT NULL,
    first_name        VARCHAR(100) NOT NULL,
    last_name         VARCHAR(100) NOT NULL
);


CREATE TABLE travel_item (
    item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
);


--  Tables that depend on the four above

CREATE TABLE agency_review (
    review_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rating       TINYINT UNSIGNED NOT NULL,
    comment      TEXT,
    review_date  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    agency_id    INT UNSIGNED NOT NULL,
    CONSTRAINT chk_agency_review_rating CHECK (rating BETWEEN 1 AND 5),
    FOREIGN KEY (agency_id) REFERENCES travel_agency(user_id)
        ON DELETE CASCADE
);

-- Multi-valued attribute
CREATE TABLE agency_phone_number (
    user_id                  INT UNSIGNED NOT NULL,
    agency_cellphone_number  VARCHAR(20)  NOT NULL,
    PRIMARY KEY (user_id, agency_cellphone_number),
    FOREIGN KEY (user_id) REFERENCES travel_agency(user_id)
        ON DELETE CASCADE
);

CREATE TABLE travel_package (
    package_id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    start_date              DATE NOT NULL,
    end_date                DATE NOT NULL,
    price_per_individual    DECIMAL(12,2) NOT NULL,
    total_price_of_package  DECIMAL(12,2) NOT NULL,
    package_status          ENUM('active','inactive','fully_booked','cancelled')
                              NOT NULL DEFAULT 'active',
    agency_id               INT UNSIGNED NOT NULL,
    destination_id          INT UNSIGNED NOT NULL,
    CONSTRAINT chk_package_dates CHECK (end_date >= start_date),
    FOREIGN KEY (agency_id)     REFERENCES travel_agency(user_id),
    FOREIGN KEY (destination_id) REFERENCES destination(destination_id)
);

CREATE TABLE booking (
    booking_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_date      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    number_of_people  SMALLINT UNSIGNED NOT NULL,
    total_price       DECIMAL(12,2) NOT NULL,
    booking_status    ENUM('pending','confirmed','cancelled','completed')
                        NOT NULL DEFAULT 'pending',
    payment_status    ENUM('pending','paid','refunded','failed')
                        NOT NULL DEFAULT 'pending',
    package_id        INT UNSIGNED NOT NULL,
    user_id           INT UNSIGNED NOT NULL,
    FOREIGN KEY (package_id) REFERENCES travel_package(package_id),
    FOREIGN KEY (user_id)    REFERENCES traveler(user_id)
);

CREATE TABLE package_review (
    review_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rating       TINYINT UNSIGNED NOT NULL,
    comment      TEXT,
    review_date  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    booking_id   INT UNSIGNED NOT NULL UNIQUE,
    CONSTRAINT chk_package_review_rating CHECK (rating BETWEEN 1 AND 5),
    FOREIGN KEY (booking_id) REFERENCES booking(booking_id)
        ON DELETE CASCADE
);


--  Weak entities / multi-valued attributes of travel_package


CREATE TABLE itinerary_day (
    package_id    INT UNSIGNED NOT NULL,
    day_number    TINYINT UNSIGNED NOT NULL,
    day_activity  TEXT NOT NULL,
    PRIMARY KEY (package_id, day_number),
    FOREIGN KEY (package_id) REFERENCES travel_package(package_id)
        ON DELETE CASCADE
);

-- One package has many images. Original spec marked only package_id as PK
-- which can't hold multiple rows per package — made (package_id, image_url) composite.
CREATE TABLE travel_package_images (
    package_id  INT UNSIGNED NOT NULL,
    image_url   VARCHAR(500) NOT NULL,                            -- URL/path, not the bytes
    PRIMARY KEY (package_id, image_url),
    FOREIGN KEY (package_id) REFERENCES travel_package(package_id)
        ON DELETE CASCADE
);

CREATE TABLE group_trip (
    package_id     INT UNSIGNED NOT NULL,
    group_trip_id  INT UNSIGNED NOT NULL,
    no_of_people   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    max_people     SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (package_id, group_trip_id),
    CONSTRAINT chk_group_capacity CHECK (no_of_people <= max_people),
    FOREIGN KEY (package_id) REFERENCES travel_package(package_id)
        ON DELETE CASCADE
);



--  Junction: Travel_Package & Travel_Item

CREATE TABLE includes (
    package_id  INT UNSIGNED NOT NULL,
    item_id     INT UNSIGNED NOT NULL,
    PRIMARY KEY (package_id, item_id),
    FOREIGN KEY (package_id) REFERENCES travel_package(package_id)
        ON DELETE CASCADE,
    FOREIGN KEY (item_id)    REFERENCES travel_item(item_id)
        ON DELETE CASCADE
);


--  Travel_Item subtypes

CREATE TABLE flight (
    item_id              INT UNSIGNED PRIMARY KEY,
    airline              VARCHAR(100)  NOT NULL,
    flight_price         DECIMAL(10,2) NOT NULL,
    departure_date_time  DATETIME      NOT NULL,
    arrival_date_time    DATETIME      NOT NULL,
    departure_airport    VARCHAR(100)  NOT NULL,                  -- fits IATA codes or full names
    arrival_airport      VARCHAR(100)  NOT NULL,
    CONSTRAINT chk_flight_times CHECK (arrival_date_time > departure_date_time),
    FOREIGN KEY (item_id) REFERENCES travel_item(item_id)
        ON DELETE CASCADE
);

CREATE TABLE accommodation (
    item_id               INT UNSIGNED PRIMARY KEY,
    accommodation_name    VARCHAR(150)  NOT NULL,
    accommodation_price   DECIMAL(10,2) NOT NULL,
    checkin_date_time     DATETIME      NOT NULL,
    checkout_date_time    DATETIME      NOT NULL,
    price_per_night_pp    DECIMAL(10,2) NOT NULL,
    accommodation_street  VARCHAR(150)  NOT NULL,
    accommodation_number  VARCHAR(20)   NOT NULL,                 -- VARCHAR because "12A" / "B-4" exist
    accommodation_town    VARCHAR(100)  NOT NULL,
    CONSTRAINT chk_accom_dates CHECK (checkout_date_time > checkin_date_time),
    FOREIGN KEY (item_id) REFERENCES travel_item(item_id)
        ON DELETE CASCADE
);

CREATE TABLE tourist_attraction (
    item_id            INT UNSIGNED PRIMARY KEY,
    attraction_name    VARCHAR(150)  NOT NULL,
    activity_fee       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    operational_hours  VARCHAR(100),
    attraction_street  VARCHAR(150)  NOT NULL,
    attraction_number  VARCHAR(20)   NOT NULL,
    attraction_town    VARCHAR(100)  NOT NULL,
    FOREIGN KEY (item_id) REFERENCES travel_item(item_id)
        ON DELETE CASCADE
);

CREATE TABLE restaurant (
    item_id            INT UNSIGNED PRIMARY KEY,
    restaurant_name    VARCHAR(150)  NOT NULL,
    average_price_pp   DECIMAL(10,2) NOT NULL,
    operational_hours  VARCHAR(100),
    restaurant_street  VARCHAR(150)  NOT NULL,
    restaurant_number  VARCHAR(20)   NOT NULL,
    restaurant_town    VARCHAR(100)  NOT NULL,
    FOREIGN KEY (item_id) REFERENCES travel_item(item_id)
        ON DELETE CASCADE
);
