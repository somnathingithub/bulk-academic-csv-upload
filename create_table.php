<?php
require_once 'database.php'; // Replace 'path/to/Database.php' with the actual path to your Database class file.

try {
    $dbConfigPath = '.env'; // Replace 'path/to/your/database/config/file.env' with the actual path to your database configuration file.

    // Create a new instance of the Database class and pass the path to the database config file.
    $database = new Database($dbConfigPath);

    // Get a PDO connection
    $pdo = $database->connect();

    // SQL query to create the tables
    $sql = "
    -- Table: branches
    CREATE TABLE branches (
        br_id INT PRIMARY KEY AUTO_INCREMENT,
        br_name VARCHAR(255) UNIQUE
    );

    -- Table: fee_category
    CREATE TABLE fee_category (
    id INT AUTO_INCREMENT,
    br_id INT,
    freecategory VARCHAR(255),
    FOREIGN KEY (br_id) REFERENCES branches(br_id),
    PRIMARY KEY(id),
    UNIQUE INDEX(br_id, freecategory)
);


    -- Table: freecollectiontype
    CREATE TABLE freecollectiontype (
        id INT PRIMARY KEY AUTO_INCREMENT,
        br_id INT,
        collectionhead VARCHAR(255),
        collectiondesc TEXT,
        FOREIGN KEY (br_id) REFERENCES branches(br_id)
    );

    -- Table: module
    CREATE TABLE module (
        id INT PRIMARY KEY AUTO_INCREMENT,
        module VARCHAR(255) UNIQUE,
        module_id INT
    );

    -- Table: fee_types
    CREATE TABLE fee_types (
        id INT PRIMARY KEY AUTO_INCREMENT,
        fee_category INT NULL,
        fname VARCHAR(255),
        collection_id INT,
        br_id INT,
        fee_type_ledger VARCHAR(255),
        freeHeadType INT NULL,
        FOREIGN KEY (fee_category) REFERENCES fee_category(id),
        FOREIGN KEY (collection_id) REFERENCES freecollectiontype(id),
        FOREIGN KEY (br_id) REFERENCES branches(br_id),
        FOREIGN KEY (freeHeadType) REFERENCES module(id)
    );

    -- Table: Entry_mode
    CREATE TABLE Entry_mode (
        id INT PRIMARY KEY AUTO_INCREMENT,
        entry_modename VARCHAR(255),
        crdr VARCHAR(255),
        entry_modeno INT
    );

    -- Table: financial_trans (Parent)
    CREATE TABLE financial_trans (
        id INT PRIMARY KEY AUTO_INCREMENT,
        module_id INT,
        adm_no VARCHAR(255),
        amount DECIMAL(10, 2),
        crdr VARCHAR(255),
        tran_date DATE,
        acad_year VARCHAR(255),
        entry_mode INT,
        voucher_no INT,
        br_id INT,
        type_of_consession INT,
        FOREIGN KEY (module_id) REFERENCES module(id),
        FOREIGN KEY (entry_mode) REFERENCES Entry_mode(id),
        FOREIGN KEY (br_id) REFERENCES branches(br_id)
    );

    -- Table: financial_tran_details (Child)
    CREATE TABLE financial_tran_details (
        id INT PRIMARY KEY AUTO_INCREMENT,
        financial_trans_id INT,
        module_id INT,
        amount DECIMAL(10, 2),
        head_id INT,
        crdr VARCHAR(255),
        br_id INT,
        head_name VARCHAR(255),
        FOREIGN KEY (financial_trans_id) REFERENCES financial_trans(id),
        FOREIGN KEY (module_id) REFERENCES financial_trans(module_id),
        FOREIGN KEY (head_id) REFERENCES fee_types(id),
        FOREIGN KEY (br_id) REFERENCES branches(br_id)
    );

    -- Table: common_fee_collection (Parent)
    CREATE TABLE common_fee_collection (
        id INT PRIMARY KEY AUTO_INCREMENT,
        module_id INT,
        adm_no VARCHAR(255),
        roll_no VARCHAR(255),
        amount DECIMAL(10, 2),
        br_id INT,
        academic_year VARCHAR(255),
        financial_year VARCHAR(255),
        display_receipt_no VARCHAR(255),
        entry_mode INT,
        paid_date DATE,
        FOREIGN KEY (module_id) REFERENCES module(id),
        FOREIGN KEY (entry_mode) REFERENCES Entry_mode(id),
        FOREIGN KEY (br_id) REFERENCES branches(br_id)
    );

    -- Table: common_fee_collection_headwies (Child)
    CREATE TABLE common_fee_collection_headwies (
        id INT PRIMARY KEY AUTO_INCREMENT,
        module_id INT,
        receipt_id INT,
        head_id INT,
        head_name VARCHAR(255),
        br_id INT,
        amount DECIMAL(10, 2),
        FOREIGN KEY (module_id) REFERENCES module(id),
        FOREIGN KEY (receipt_id) REFERENCES common_fee_collection(id),
        FOREIGN KEY (head_id) REFERENCES fee_types(id),
        FOREIGN KEY (br_id) REFERENCES branches(br_id)
    );

    -- Insert predefined data into the module table
    INSERT INTO module (module, module_id) VALUES
    ('Academic', 1),
    ('Academic Misc', 11),
    ('Hostel', 2),
    ('Hostel Misc', 22),
    ('Transport', 3),
    ('Transport Misc', 33);

    -- Insert predefined data into the Entry_mode table
    INSERT INTO Entry_mode (entry_modename, crdr, entry_modeno) VALUES
    ('due', 'D', 0),
    ('REVDUE', 'c', 12),
    ('scholarship', 'c', 15),
    ('scholarshiprev/revconsessiol', 'D', 16),
    ('concession', 'c', 15),
    ('RCPT', 'c', 0),
    ('REVRCPT', 'D', 0),
    ('Jv.', 'c', 14),
    ('Rewv', 'D', 14),
    ('PMT', 'D', 1),
    ('REVPMT', 'c', 1),
    ('Fundtransfer', 'positive and negative', '1');
    ";

    // Execute the query
    $affectedRows = $pdo->exec($sql);
    if ($affectedRows !== false) {
        echo "Tables created successfully.";
    } else {
        $errorInfo = $pdo->errorInfo();
        echo "Error creating tables: " . $errorInfo[2];
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
