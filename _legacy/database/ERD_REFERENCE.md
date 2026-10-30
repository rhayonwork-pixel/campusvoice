# CampusVoice ERD — Final Reference

The logical ERD uses the following entities and relationships.

```mermaid
erDiagram
    DEPARTMENTS ||--o{ USERS : contains
    USERS ||--o{ CONCERNS : submits
    CATEGORIES ||--o{ CONCERNS : categorizes
    LOCATIONS ||--o{ CONCERNS : identifies
    PRIORITIES ||--o{ CONCERNS : prioritizes
    STATUSES ||--o{ CONCERNS : tracks
    CONCERNS ||--o{ ASSIGNMENTS : has
    USERS ||--o{ ASSIGNMENTS : assigned_staff
    USERS ||--o{ ASSIGNMENTS : assigned_by
    CONCERNS ||--o{ CONCERN_UPDATES : records
    USERS ||--o{ CONCERN_UPDATES : creates
    CONCERNS ||--o{ PHOTOS : contains
    USERS ||--o{ PHOTOS : uploads
    USERS ||--o{ NOTIFICATIONS : receives
    CONCERNS ||--o{ NOTIFICATIONS : relates_to
    CONCERNS ||--o| FEEDBACK : receives
    USERS ||--o{ FEEDBACK : submits

    DEPARTMENTS {
        bigint department_id PK
        text department_name UK
        enum department_type
        text description
        enum status
    }
    USERS {
        uuid user_id PK
        text student_id UK
        text staff_id UK
        text admin_id UK
        text first_name
        text middle_initial
        text last_name
        text email UK
        enum role
        bigint department_id FK
        text position
        text coverage
        text contact_number
        text profile_picture
        enum account_status
    }
    CONCERNS {
        bigint concern_id PK
        text concern_code UK
        uuid student_id FK
        bigint category_id FK
        bigint location_id FK
        bigint priority_id FK
        bigint status_id FK
        text title
        text description
        timestamptz date_submitted
        timestamptz resolved_at
        timestamptz closed_at
    }
    CATEGORIES {
        bigint category_id PK
        text category_name UK
        text description
        enum status
    }
    LOCATIONS {
        bigint location_id PK
        text building
        text floor
        text area
        enum status
    }
    PRIORITIES {
        bigint priority_id PK
        text priority_name UK
        numeric target_response_hours
        numeric target_resolution_hours
        enum status
    }
    STATUSES {
        bigint status_id PK
        text status_name UK
        text description
        text status_type
    }
    ASSIGNMENTS {
        bigint assignment_id PK
        bigint concern_id FK
        uuid staff_id FK
        uuid assigned_by FK
        timestamptz assigned_at
        timestamptz reassigned_at
        enum assignment_status
        text reason
    }
    CONCERN_UPDATES {
        bigint update_id PK
        bigint concern_id FK
        uuid user_id FK
        bigint status_id FK
        text update_type
        text response
        text action_taken
        timestamptz created_at
    }
    PHOTOS {
        bigint photo_id PK
        bigint concern_id FK
        uuid uploaded_by FK
        enum photo_type
        text file_path
        timestamptz created_at
    }
    NOTIFICATIONS {
        bigint notification_id PK
        uuid user_id FK
        bigint concern_id FK
        text title
        text message
        boolean is_read
        timestamptz created_at
    }
    FEEDBACK {
        bigint feedback_id PK
        bigint concern_id FK
        uuid student_id FK
        smallint rating
        text comment
        timestamptz created_at
    }
```

### Supporting system tables
`WORKING_HOURS`, `HOLIDAYS`, `SYSTEM_SETTINGS`, and `ACTIVITY_LOGS` support configuration, calendar rules, system settings, and monitoring. They are not part of the main concern relationship chain but are included in the database implementation.
