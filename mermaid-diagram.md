# Nexus AI WP Translator - Architectural Diagram

## Plugin Architecture Overview

```mermaid
graph TB
    subgraph "WordPress Environment"
        WP[WordPress Core]
        ADMIN[Admin Interface]
        FRONTEND[Frontend Interface]
        DB[(WordPress Database)]
    end

    subgraph "Plugin Core Components"
        MAIN[nexus-ai-wp-translator.php<br/>Main Plugin Controller]
        
        subgraph "Core Classes"
            TM[Translation Manager<br/>class-translation-manager.php]
            API[API Handler<br/>class-api-handler.php]
            DATABASE[Database<br/>class-database.php]
            ADMIN_CLASS[Admin<br/>class-admin.php]
            FRONTEND_CLASS[Frontend<br/>class-frontend.php]
            WORKFLOW[Workflow Manager<br/>class-workflow-manager.php]
        end

        subgraph "Feature Classes"
            SETTINGS[Settings<br/>class-settings.php]
            QA[Quality Assessor<br/>class-quality-assessor.php]
            SEO[SEO Optimizer<br/>class-seo-optimizer.php]
            SCHEDULER[Translation Scheduler<br/>class-translation-scheduler.php]
            ANALYTICS[Analytics<br/>class-analytics.php]
            MEMORY[Translation Memory<br/>class-translation-memory.php]
            CUSTOM[Custom Fields Translator<br/>class-custom-fields-translator.php]
            ERROR[Error Handler<br/>class-error-handler.php]
            TEMPLATES[Translation Templates<br/>class-translation-templates.php]
            SWITCHER[Language Switcher<br/>class-language-switcher.php]
            GUTENBERG[Gutenberg Block<br/>class-gutenberg-block.php]
        end
    end

    subgraph "External Services"
        CLAUDE[Claude AI API<br/>api.anthropic.com]
    end

    subgraph "Database Tables"
        TRANS_TABLE[nexus_ai_wp_translations<br/>Translation Relationships]
        LOGS_TABLE[nexus_ai_wp_translation_logs<br/>Activity Logs]
        PREFS_TABLE[nexus_ai_wp_user_preferences<br/>User Language Preferences]
        WORKFLOW_TABLE[nexus_ai_translation_workflow<br/>Workflow Items]
        COMMENTS_TABLE[nexus_ai_translation_workflow_comments<br/>Workflow Comments]
        HISTORY_TABLE[nexus_ai_translation_workflow_history<br/>Workflow History]
    end

    %% Core Dependencies
    MAIN --> TM
    MAIN --> API
    MAIN --> DATABASE
    MAIN --> ADMIN_CLASS
    MAIN --> FRONTEND_CLASS
    MAIN --> WORKFLOW

    %% Manager Dependencies
    TM --> API
    TM --> DATABASE
    TM --> QA
    TM --> SEO
    
    API --> CLAUDE
    API --> MEMORY
    API --> ERROR

    ADMIN_CLASS --> TM
    ADMIN_CLASS --> DATABASE
    ADMIN_CLASS --> API

    FRONTEND_CLASS --> DATABASE
    FRONTEND_CLASS --> SWITCHER

    WORKFLOW --> DATABASE

    %% Database Connections
    DATABASE --> TRANS_TABLE
    DATABASE --> LOGS_TABLE
    DATABASE --> PREFS_TABLE
    WORKFLOW --> WORKFLOW_TABLE
    WORKFLOW --> COMMENTS_TABLE
    WORKFLOW --> HISTORY_TABLE

    %% WordPress Integration
    ADMIN_CLASS --> ADMIN
    FRONTEND_CLASS --> FRONTEND
    DATABASE --> DB
    GUTENBERG --> WP

    %% Feature Dependencies
    TM --> SCHEDULER
    TM --> ANALYTICS
    TM --> CUSTOM
    TM --> TEMPLATES
    API --> SETTINGS

    style MAIN fill:#e1f5fe
    style CLAUDE fill:#ffecb3
    style DB fill:#f3e5f5
```

## Translation Workflow Process

```mermaid
flowchart TD
    START([User Initiates Translation])
    
    subgraph "Pre-Translation Validation"
        CHECK_API{API Key Valid?}
        CHECK_THROTTLE{Within Rate Limits?}
        CHECK_POST{Post Exists?}
        CHECK_LANG{Valid Target Languages?}
    end

    subgraph "Content Validation & Processing"
        VALIDATE_CONTENT{Content Not Empty?}
        SPLIT[Split Content into Blocks]
        VALIDATE_BLOCKS{Valid Blocks Found?}
        CACHE_CHECK{Check Translation Cache}
        PREP_CONTEXT[Prepare Translation Context]
    end

    subgraph "AI Translation"
        API_CALL[Claude API Request]
        PROCESS_RESPONSE[Process AI Response]
        VALIDATE_RESPONSE{Valid Translation Response?}
        FILTER_COMMENTS[Filter AI Comments/Apologies]
        LOG_INVALID[Log Invalid Response]
        QUALITY_CHECK[Quality Assessment]
        RETRY{Retry if Failed?}
    end

    subgraph "Content Creation"
        CREATE_POST[Create Translated Post]
        COPY_META[Copy Post Metadata]
        TRANSLATE_CATS[Translate Categories]
        TRANSLATE_TAGS[Translate Tags]
        SEO_META[Add SEO Metadata]
    end

    subgraph "Post-Processing"
        STORE_RELATIONSHIP[Store Translation Relationship]
        UPDATE_CACHE[Update Translation Cache]
        LOG_ACTIVITY[Log Translation Activity]
        QUALITY_STORE[Store Quality Assessment]
    end

    subgraph "Workflow Management (Optional)"
        WORKFLOW_CHECK{Workflow Enabled?}
        CREATE_WORKFLOW[Create Workflow Item]
        ASSIGN_REVIEWER[Assign Reviewer]
        NOTIFY_TEAM[Send Notifications]
    end

    subgraph "Completion"
        SUCCESS([Translation Complete])
        PUBLISH{Auto-Publish?}
        DRAFT[Save as Draft]
        LIVE[Publish Live]
    end

    START --> CHECK_API
    CHECK_API -->|No| ERROR1[Return API Error]
    CHECK_API -->|Yes| CHECK_THROTTLE
    CHECK_THROTTLE -->|No| ERROR2[Return Rate Limit Error]
    CHECK_THROTTLE -->|Yes| CHECK_POST
    CHECK_POST -->|No| ERROR3[Return Post Error]
    CHECK_POST -->|Yes| CHECK_LANG
    CHECK_LANG -->|No| ERROR4[Return Language Error]
    CHECK_LANG -->|Yes| VALIDATE_CONTENT
    
    VALIDATE_CONTENT -->|No| SKIP_EMPTY[Skip Empty Content - No API Call]
    VALIDATE_CONTENT -->|Yes| SPLIT
    SPLIT --> VALIDATE_BLOCKS
    VALIDATE_BLOCKS -->|No| SKIP_EMPTY
    VALIDATE_BLOCKS -->|Yes| CACHE_CHECK
    SKIP_EMPTY --> SUCCESS
    CACHE_CHECK -->|Hit| USE_CACHE[Use Cached Translation]
    CACHE_CHECK -->|Miss| PREP_CONTEXT
    PREP_CONTEXT --> API_CALL

    API_CALL --> PROCESS_RESPONSE
    PROCESS_RESPONSE --> VALIDATE_RESPONSE
    VALIDATE_RESPONSE -->|Invalid/Comments| LOG_INVALID
    VALIDATE_RESPONSE -->|Valid| FILTER_COMMENTS
    FILTER_COMMENTS --> QUALITY_CHECK
    LOG_INVALID --> RETRY
    QUALITY_CHECK --> RETRY
    RETRY -->|Yes| API_CALL
    RETRY -->|No| CREATE_POST

    USE_CACHE --> CREATE_POST

    CREATE_POST --> COPY_META
    COPY_META --> TRANSLATE_CATS
    TRANSLATE_CATS --> TRANSLATE_TAGS
    TRANSLATE_TAGS --> SEO_META
    SEO_META --> STORE_RELATIONSHIP

    STORE_RELATIONSHIP --> UPDATE_CACHE
    UPDATE_CACHE --> LOG_ACTIVITY
    LOG_ACTIVITY --> QUALITY_STORE
    QUALITY_STORE --> WORKFLOW_CHECK

    WORKFLOW_CHECK -->|Yes| CREATE_WORKFLOW
    WORKFLOW_CHECK -->|No| PUBLISH
    CREATE_WORKFLOW --> ASSIGN_REVIEWER
    ASSIGN_REVIEWER --> NOTIFY_TEAM
    NOTIFY_TEAM --> SUCCESS

    PUBLISH -->|Yes| LIVE
    PUBLISH -->|No| DRAFT
    DRAFT --> SUCCESS
    LIVE --> SUCCESS

    style START fill:#c8e6c9
    style SUCCESS fill:#c8e6c9
    style ERROR1 fill:#ffcdd2
    style ERROR2 fill:#ffcdd2
    style ERROR3 fill:#ffcdd2
    style ERROR4 fill:#ffcdd2
```

## Database Schema Relationships

```mermaid
erDiagram
    wp_posts ||--o{ nexus_ai_wp_translations : "source_post_id"
    wp_posts ||--o{ nexus_ai_wp_translations : "translated_post_id"
    wp_posts ||--o{ nexus_ai_wp_translation_logs : "post_id"
    wp_users ||--o{ nexus_ai_wp_user_preferences : "user_id"
    wp_users ||--o{ nexus_ai_translation_workflow : "submitted_by"
    wp_users ||--o{ nexus_ai_translation_workflow : "assigned_reviewer"
    
    nexus_ai_wp_translations {
        bigint id PK
        bigint source_post_id FK
        bigint translated_post_id FK
        varchar source_language
        varchar target_language
        varchar status
        datetime created_at
        datetime updated_at
    }

    nexus_ai_wp_translation_logs {
        bigint id PK
        bigint post_id FK
        varchar action
        varchar status
        text message
        int api_calls_count
        float processing_time
        datetime created_at
    }

    nexus_ai_wp_user_preferences {
        bigint id PK
        bigint user_id FK
        varchar preferred_language
        datetime created_at
        datetime updated_at
    }

    nexus_ai_translation_workflow {
        bigint id PK
        bigint post_id FK
        bigint translated_post_id FK
        varchar source_language
        varchar target_language
        varchar status
        int priority
        bigint assigned_reviewer FK
        bigint submitted_by FK
        datetime submitted_at
        datetime reviewed_at
        datetime due_date
        varchar workflow_type
        text metadata
        datetime created_at
        datetime updated_at
    }

    nexus_ai_translation_workflow_comments {
        bigint id PK
        bigint workflow_id FK
        bigint user_id FK
        varchar comment_type
        text comment_text
        text metadata
        datetime created_at
    }

    nexus_ai_translation_workflow_history {
        bigint id PK
        bigint workflow_id FK
        bigint user_id FK
        varchar action
        varchar old_status
        varchar new_status
        text notes
        datetime created_at
    }

    nexus_ai_translation_workflow ||--o{ nexus_ai_translation_workflow_comments : "workflow_id"
    nexus_ai_translation_workflow ||--o{ nexus_ai_translation_workflow_history : "workflow_id"
```

## API Integration Flow

```mermaid
sequenceDiagram
    participant User
    participant Admin as Admin Interface
    participant TM as Translation Manager
    participant API as API Handler
    participant Cache as Translation Cache
    participant Claude as Claude AI API
    participant DB as Database

    User->>Admin: Initiate Translation
    Admin->>TM: translate_post(post_id, languages)
    
    TM->>API: translate_post_content(post_id, target_lang)
    API->>Cache: get_cached_translation()
    
    alt Cache Hit
        Cache-->>API: Return cached translation
    else Cache Miss
        API->>Claude: POST /v1/messages
        note over Claude: AI Translation Processing
        Claude-->>API: Translation Response
        API->>Cache: cache_translation()
    end
    
    API-->>TM: Translation Result
    
    TM->>DB: create_translated_post()
    TM->>DB: store_translation_relationship()
    TM->>DB: log_translation_activity()
    
    TM-->>Admin: Success Response
    Admin-->>User: Display Results

    note over API,Claude: Rate Limiting & Retry Logic
    note over Cache: 24 Hour Cache Duration
    note over TM: Quality Assessment & SEO Processing
```

## Frontend Language Management

```mermaid
flowchart TD
    subgraph "User Access"
        VISITOR[Website Visitor]
        BROWSER[Browser Request]
    end

    subgraph "Language Detection"
        URL_PARAM{URL Lang Parameter?}
        USER_PREF{Logged In User Preference?}
        SESSION_LANG{Session Language?}
        COOKIE_LANG{Cookie Language?}
        BROWSER_LANG{Browser Accept-Language?}
        DEFAULT_LANG[Default Source Language]
    end

    subgraph "Content Resolution"
        CURRENT_POST[Current Post/Page]
        CHECK_TRANSLATION{Translation Exists?}
        REDIRECT_TRANSLATION[Redirect to Translation]
        STAY_CURRENT[Stay on Current Page]
    end

    subgraph "Language Switcher"
        RENDER_SWITCHER[Render Language Switcher]
        DROPDOWN[Dropdown Style]
        LIST[List Style]
        FLAGS[With/Without Flags]
    end

    subgraph "User Interaction"
        LANG_SELECT[User Selects Language]
        STORE_PREFERENCE[Store Language Preference]
        UPDATE_SESSION[Update Session]
        SET_COOKIE[Set Cookie]
        RELOAD_PAGE[Redirect to Language Version]
    end

    VISITOR --> BROWSER
    BROWSER --> URL_PARAM
    
    URL_PARAM -->|Yes| CURRENT_POST
    URL_PARAM -->|No| USER_PREF
    USER_PREF -->|Yes| CURRENT_POST
    USER_PREF -->|No| SESSION_LANG
    SESSION_LANG -->|Yes| CURRENT_POST
    SESSION_LANG -->|No| COOKIE_LANG
    COOKIE_LANG -->|Yes| CURRENT_POST
    COOKIE_LANG -->|No| BROWSER_LANG
    BROWSER_LANG -->|Yes| CURRENT_POST
    BROWSER_LANG -->|No| DEFAULT_LANG
    DEFAULT_LANG --> CURRENT_POST

    CURRENT_POST --> CHECK_TRANSLATION
    CHECK_TRANSLATION -->|Yes| REDIRECT_TRANSLATION
    CHECK_TRANSLATION -->|No| STAY_CURRENT
    
    REDIRECT_TRANSLATION --> RENDER_SWITCHER
    STAY_CURRENT --> RENDER_SWITCHER
    
    RENDER_SWITCHER --> DROPDOWN
    RENDER_SWITCHER --> LIST
    RENDER_SWITCHER --> FLAGS
    
    DROPDOWN --> LANG_SELECT
    LIST --> LANG_SELECT
    FLAGS --> LANG_SELECT
    
    LANG_SELECT --> STORE_PREFERENCE
    STORE_PREFERENCE --> UPDATE_SESSION
    UPDATE_SESSION --> SET_COOKIE
    SET_COOKIE --> RELOAD_PAGE

    style VISITOR fill:#e3f2fd
    style REDIRECT_TRANSLATION fill:#c8e6c9
    style RELOAD_PAGE fill:#c8e6c9
```

## Admin Interface Structure

```mermaid
graph TB
    subgraph "WordPress Admin Menu"
        MAIN_MENU[Nexus AI WP Translator]
        
        subgraph "Menu Pages"
            DASHBOARD[Dashboard<br/>admin-dashboard.php]
            SETTINGS[Settings<br/>admin-settings.php]
            LOGS[Translation Logs<br/>admin-logs.php]
            RELATIONSHIPS[Post Relationships<br/>admin-relationships.php]
        end
    end

    subgraph "Dashboard Features"
        STATS[Translation Statistics]
        RECENT_LOGS[Recent Activity]
        POSTS_LIST[Posts Management]
        BULK_ACTIONS[Bulk Actions]
        QUICK_TRANSLATE[Quick Translation]
    end

    subgraph "Settings Sections"
        API_SETTINGS[API Configuration]
        LANG_SETTINGS[Language Settings]
        WORKFLOW_SETTINGS[Workflow Settings]
        PERF_SETTINGS[Performance & Rate Limiting]
        NOTIF_SETTINGS[Notification Settings]
    end

    subgraph "Post Edit Integration"
        META_BOX[Translation Meta Box]
        POST_COLUMNS[Post List Columns]
        TRANSLATE_BUTTON[Translate Button]
        STATUS_DISPLAY[Translation Status]
    end

    subgraph "AJAX Operations"
        TEST_API[Test API Connection]
        GET_MODELS[Get Available Models]
        TRANSLATE_POST[Translate Post]
        BULK_OPERATIONS[Bulk Operations]
        MANAGE_RELATIONSHIPS[Manage Relationships]
    end

    MAIN_MENU --> DASHBOARD
    MAIN_MENU --> SETTINGS
    MAIN_MENU --> LOGS
    MAIN_MENU --> RELATIONSHIPS

    DASHBOARD --> STATS
    DASHBOARD --> RECENT_LOGS
    DASHBOARD --> POSTS_LIST
    POSTS_LIST --> BULK_ACTIONS
    POSTS_LIST --> QUICK_TRANSLATE

    SETTINGS --> API_SETTINGS
    SETTINGS --> LANG_SETTINGS
    SETTINGS --> WORKFLOW_SETTINGS
    SETTINGS --> PERF_SETTINGS
    SETTINGS --> NOTIF_SETTINGS

    META_BOX --> TRANSLATE_BUTTON
    META_BOX --> STATUS_DISPLAY
    POST_COLUMNS --> TRANSLATE_BUTTON

    TRANSLATE_BUTTON --> TRANSLATE_POST
    BULK_ACTIONS --> BULK_OPERATIONS
    API_SETTINGS --> TEST_API
    API_SETTINGS --> GET_MODELS

    style MAIN_MENU fill:#e1f5fe
    style AJAX_OPERATIONS fill:#fff3e0
```

## Team Workflow Process

```mermaid
stateDiagram-v2
    [*] --> Draft : Translation Created

    Draft --> PendingReview : Submit for Review
    Draft --> Approved : Auto-Approve (if enabled)
    
    PendingReview --> InReview : Reviewer Assigned
    PendingReview --> Approved : Quick Approve
    PendingReview --> Rejected : Reject
    
    InReview --> Approved : Approve
    InReview --> Rejected : Reject
    InReview --> PendingReview : Reassign
    
    Rejected --> Draft : Revise & Resubmit
    Rejected --> PendingReview : Resubmit
    
    Approved --> Published : Publish Translation
    
    Published --> [*] : Complete

    note right of Draft
        - Translation completed by AI
        - Quality assessment performed
        - Awaiting human review
    end note

    note right of PendingReview
        - Submitted for team review
        - Reviewer notification sent
        - Comments can be added
    end note

    note right of InReview
        - Assigned reviewer working
        - Collaborative feedback
        - Revision requests possible
    end note

    note right of Approved
        - Translation approved
        - Ready for publication
        - Notification sent to submitter
    end note

    note right of Rejected
        - Needs improvement
        - Feedback provided
        - Can be revised and resubmitted
    end note
```

## Key Integration Points

```mermaid
mindmap
  root((Nexus AI WP Translator))
    WordPress Core
      Hooks & Actions
      Admin Interface
      Database Layer
      User Management
      Post Management
    Claude AI API
      Authentication
      Model Selection
      Rate Limiting
      Error Handling
      Response Processing
    Translation Features
      Content Processing
      Quality Assessment
      SEO Optimization
      Category/Tag Translation
      Custom Field Support
    User Experience
      Language Detection
      Auto Redirection
      Language Switcher
      Progress Tracking
      Bulk Operations
    Team Collaboration
      Workflow Management
      Review Process
      Notifications
      Role Management
      Activity Tracking
```

---

## Summary

This architectural diagram documents the complete structure of the Nexus AI WP Translator WordPress plugin, showing:

1. **Component Architecture** - How all classes and modules interact
2. **Translation Workflow** - Step-by-step process from initiation to completion
3. **Database Design** - Table relationships and data storage
4. **API Integration** - External service communication patterns
5. **Frontend Management** - User language handling and content routing
6. **Admin Interface** - Management tools and user interactions
7. **Team Workflow** - Collaborative review and approval processes

The plugin follows WordPress best practices with proper separation of concerns, secure AJAX handlers, and comprehensive error handling throughout the translation pipeline.
