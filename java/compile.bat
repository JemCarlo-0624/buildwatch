@echo off
REM Windows batch script to download MySQL JDBC driver and compile ReportGenerator

echo BuildWatch Report Generator - Compilation Script
echo ================================================

REM Create lib directory if it doesn't exist
if not exist "lib" mkdir lib

REM Check if MySQL Connector is already downloaded
if not exist "lib\mysql-connector-j.jar" (
    echo Downloading MySQL JDBC Driver...
    
    REM Updated to use correct Maven repository URL for mysql-connector-j
    powershell -Command "Invoke-WebRequest -Uri 'https://repo1.maven.org/maven2/com/mysql/mysql-connector-j/8.3.0/mysql-connector-j-8.3.0.jar' -OutFile 'lib\mysql-connector-j.jar'"
    
    if %ERRORLEVEL% EQU 0 (
        echo MySQL JDBC Driver downloaded successfully
    ) else (
        echo Failed to download from Maven. Trying alternative source...
        powershell -Command "Invoke-WebRequest -Uri 'https://cdn.mysql.com/Downloads/Connector-J/mysql-connector-j-8.3.0.jar' -OutFile 'lib\mysql-connector-j.jar'"
    )
) else (
    echo MySQL JDBC Driver already exists
)

REM Compile Java file
echo.
echo Compiling ReportGenerator.java...
javac -cp ".;lib/*" ReportGenerator.java

if %ERRORLEVEL% EQU 0 (
    echo Compilation successful!
    echo.
    echo Usage: java -cp ".;lib/*" ReportGenerator ^<project_id^> ^<format^>
    echo Formats: html, json, txt
) else (
    echo Compilation failed!
    exit /b 1
)
