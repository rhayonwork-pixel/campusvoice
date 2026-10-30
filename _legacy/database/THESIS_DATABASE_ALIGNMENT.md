# Thesis ↔ Database Alignment Note

A review of the current Chapter 2 draft found that its Database Layer paragraph describes **MySQL**, while the current implementation package is built around **Supabase PostgreSQL**.

For a consistent final manuscript, the database technology named in Chapter 2 must match the technology actually used by the project.

## Recommended wording for the current implementation

> **Database Layer**  
> The Database Layer is responsible for storing, organizing, and managing the information required by the CampusVoice system using **PostgreSQL through Supabase**. It contains records such as user information, departments, concerns, categories, locations, priorities, statuses, photos, assignments, concern updates, notifications, feedback, and activity logs. The database maintains organized records of submitted concerns, including concern details, assigned staff, status updates, responses, actions taken, and supporting or evidence photos. It also supports system configuration such as working hours, holidays, and system settings. Appropriate relationships, constraints, and row-level security policies are used to protect and organize the stored data.

Do not state that the implementation uses MySQL if the group proceeds with the current Supabase/PostgreSQL backend.
