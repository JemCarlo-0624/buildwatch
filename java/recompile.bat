@echo off
REM Quick recompile script for ReportGenerator

cd /d "%~dp0"

echo Recompiling ReportGenerator.java...
javac -cp ".;lib/*" ReportGenerator.java

if %ERRORLEVEL% EQU 0 (
    echo.
    echo Compilation successful!
    echo.
    echo Report generator is ready to use.
) else (
    echo.
    echo Compilation failed!
    exit /b 1
)
