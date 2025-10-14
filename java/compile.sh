#!/bin/bash

echo "BuildWatch Report Generator - Compilation Script"
echo "================================================"

# Create lib directory if it doesn't exist
mkdir -p lib

# Check if MySQL Connector is already downloaded
if [ ! -f "lib/mysql-connector-j.jar" ]; then
    echo "Downloading MySQL JDBC Driver..."
    
    curl -L -o lib/mysql-connector-j-8.3.0.jar \
        "https://repo1.maven.org/maven2/com/mysql/mysql-connector-j/8.3.0/mysql-connector-j-8.3.0.jar"
    
    if [ $? -eq 0 ]; then
        # Create symlink for easier reference
        ln -sf mysql-connector-j-8.3.0.jar lib/mysql-connector-j.jar
        echo "✓ MySQL JDBC Driver downloaded successfully"
    else
        echo "Failed to download from Maven. Trying alternative source..."
        curl -L -o lib/mysql-connector-j.jar \
            "https://cdn.mysql.com/Downloads/Connector-J/mysql-connector-j-8.3.0.jar"
    fi
else
    echo "✓ MySQL JDBC Driver already exists"
fi

# Compile Java file
echo ""
echo "Compiling ReportGenerator.java..."
javac -cp ".:lib/*" ReportGenerator.java

if [ $? -eq 0 ]; then
    echo "✓ Compilation successful!"
    echo ""
    echo "Usage: java -cp '.:lib/*' ReportGenerator <project_id> <format>"
    echo "Formats: html, json, txt"
else
    echo "✗ Compilation failed!"
    exit 1
fi
