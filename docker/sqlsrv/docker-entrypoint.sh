#!/bin/bash

# Function to initialize the database
initialize_db() {
    # Wait for SQL Server to be ready
    echo "Waiting for SQL Server to be ready..."
    echo "Using password from MSSQL_SA_PASSWORD environment variable"
    for i in {1..60}; do
        if /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "${MSSQL_SA_PASSWORD}" -Q "SELECT 1" -C -No &> /dev/null; then
            echo "SQL Server is ready"
            break
        fi
        sleep 2
    done

    # Check if databases already exist
    DB_EXISTS=$(/opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "${MSSQL_SA_PASSWORD}" -Q "SET NOCOUNT ON; SELECT COUNT(*) FROM sys.databases WHERE name = 'cakephp_test'" -h -1 -C -No 2>/dev/null | tr -d ' ')

    if [ "$DB_EXISTS" = "0" ]; then
        echo "Initializing databases..."

        # Run initialization scripts if they exist
        if [ -d /docker-entrypoint-initdb.d ]; then
            for f in /docker-entrypoint-initdb.d/*; do
                case "$f" in
                    *.sql)
                        echo "Running $f"
                        /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "${MSSQL_SA_PASSWORD}" -i "$f" -C -No
                        ;;
                    *.sh)
                        if [ "$f" != "/docker-entrypoint-initdb.d/docker-entrypoint.sh" ]; then
                            echo "Running $f"
                            bash "$f"
                        fi
                        ;;
                    *)
                        echo "Ignoring $f"
                        ;;
                esac
            done
            echo "Initialization complete"
        fi
    else
        echo "Databases already exist, skipping initialization"
    fi
}

# Run initialization in background
initialize_db &

# Start SQL Server (this will be the main process)
exec /opt/mssql/bin/sqlservr