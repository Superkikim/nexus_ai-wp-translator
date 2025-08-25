# Changelog

All notable changes to the Nexus AI WP Translator plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added - Language System Overhaul

#### 🌍 Comprehensive Language Support
- **80+ Languages**: Complete ISO 639-1 language support including popular languages (English, Spanish, French, German, Italian, Portuguese, Russian, Chinese, Japanese, Arabic, Hindi, Dutch) and specialized languages (Basque, Catalan, Welsh, Scottish Gaelic, etc.)
- **Modern Language Grid Interface**: Searchable grid layout for language selection with real-time filtering
- **Popular Languages Filter**: Show popular languages by default with toggle for full list
- **Language Code Display**: Visual language codes alongside names for clarity

#### 🤖 AI-Powered Language Detection
- **Automatic Detection**: Claude AI automatically detects post language during translation
- **Manual Bulk Detection**: "Detect Language" bulk action for multiple posts
- **Smart Processing**: Skips posts with existing language assignments
- **Validation**: Detected languages validated against supported list
- **Fallback System**: Graceful fallback to default source language

#### ⚙️ Enhanced Language Management
- **Source Language Setting**: Configure website's primary language in Global Settings
- **Bulk Language Assignment**: Set language for multiple posts via bulk actions
- **Comprehensive Validation**: Language code validation throughout the system
- **Error Handling**: Clear error messages for invalid language operations

#### 🔧 System Improvements
- **Removed "Auto" Default**: Eliminated confusing "auto" language fallback
- **Consistent Language Display**: "Not set" display for posts without language
- **JavaScript Synchronization**: Aligned JavaScript language list with PHP
- **Performance Optimization**: Efficient language detection using content excerpts

### Changed
- **Language Settings UI**: Replaced simple checkboxes with modern searchable grid
- **Language Validation**: Enhanced validation in all AJAX handlers and settings
- **Translation Process**: Integrated automatic language detection
- **Bulk Actions**: Added language-specific operations to dashboard

### Fixed
- **Language Fallbacks**: Removed hardcoded "auto" fallbacks throughout system
- **Validation Consistency**: Unified language validation across PHP and JavaScript
- **Error Messages**: Improved error feedback for language-related operations
- **Code Standards**: Enhanced code organization and documentation

### Technical Details
- **New Methods**: `is_valid_language()`, `validate_language_codes()`, `detect_language()`
- **Enhanced AJAX**: Language validation in all bulk operations
- **CSS Improvements**: Responsive language grid with search highlighting
- **JavaScript Modules**: Modular language settings functionality
- **Testing**: Basic language validation test suite

### Documentation
- **README Updates**: Comprehensive language system documentation
- **Code Comments**: Enhanced inline documentation
- **User Guide**: Step-by-step language configuration instructions
- **API Documentation**: Language detection and validation methods

---

## [Previous Versions]

### [0.0.1] - Initial Release
- Basic translation functionality
- Claude AI integration
- WordPress admin interface
- Post relationship management
