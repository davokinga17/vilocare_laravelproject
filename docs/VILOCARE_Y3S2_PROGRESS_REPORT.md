# ViloCare System Upgrade Progress Report

## Cover Page

**Institution:** International University of East Africa  
**Faculty/School:** School of Computing and Informatics Technology  
**Course Unit:** Intermediate PHP Web Development (Frameworks)  
**Project Title:** ViloCare System Upgrade, Deployment, Security and Maintenance Report  
**Student Name:** David Otto  
**Registration Number:** BIT2211  
**Programme:** Bachelor of Information Technology  
**Year/Semester:** Year 3 Semester 2  
**Submission Date:** July 2026  

---

## Abstract

This report presents the progress made on the ViloCare system during Year 3 Semester 2. Last semester, the major milestone was the successful migration of the system to the Laravel framework. In the current semester, the focus shifted to deployment and hosting of the system online, addition of new service features, strengthening of security controls, and maintenance improvements to support real-world use. The upgraded system was prepared for online hosting using InfinityFree and published under the domain `vilocare.infinityfree.io`.

The work completed this semester includes deployment preparation, payment and receipt generation, email and SMS notification support, AI-assisted dashboard chat support, report generation and export, role-based access control, password reset and first-login password change enforcement, Google reCAPTCHA protection on login, and continued enhancement of patient, viral load, EAC, appointment, and user management modules. These improvements were intended to make the ViloCare system more secure, more accessible online, and more practical for clinical and administrative operations.

Although major progress has been achieved, some activities are still pending. These include integration of the MTN API, standardization of report forms with Ministry of Health logos, securing the live deployment further, and modernization of the AI chatbot. Overall, the system has moved from being mainly a locally improved Laravel application into a more deployable and service-oriented online health information platform.

## Chapter One: Introduction

### 1.1 Background

ViloCare is a health information management system designed to support patient tracking, viral load monitoring, appointment handling, EAC session management, reporting, and user administration. In Year 3 Semester 1, the system was successfully migrated from its previous structure into the Laravel framework. That migration created a more organized, secure, and scalable base for future upgrades.

In Year 3 Semester 2, the project emphasis changed from framework migration to operational improvement. The main concern was to deploy and host the system online, improve security, add service integrations, and strengthen maintenance features so that the system can better support practical healthcare workflows.

### 1.2 Problem Statement

Before the current semester upgrade, the system still faced several practical limitations. It needed online hosting for wider accessibility, stronger login protection, better user control, more professional reporting, integrated communication features, and better support for payments and service accountability. Without these improvements, the system would remain limited in usability and readiness for live deployment.

### 1.3 Main Objective

To improve the ViloCare system in Year 3 Semester 2 by deploying it online, enhancing security, adding communication and payment features, improving report generation, and strengthening user and maintenance controls.

### 1.4 Specific Objectives

1. To deploy the ViloCare system online using a hosting platform and domain.
2. To add payment handling and receipt generation for selected services.
3. To integrate mail, SMS, and AI-based assistant features.
4. To strengthen security using role-based access control, password policies, and anti-bot protection.
5. To improve report generation and protect entered records from unauthorized update or deletion.
6. To improve user registration by generating default passwords that must be changed at first login.
7. To identify remaining work for future completion.

### 1.5 Scope of the Semester Upgrade

This semester's work concentrated on deployment and operational upgrades built on the Laravel-based system developed in the previous semester. The work covered:

1. Online deployment preparation and hosting
2. Security enhancements
3. Notification and communication services
4. AI chat assistance
5. Payments and receipts
6. Reporting and exports
7. User access and maintenance improvements

## Chapter Two: Work Completed During the Semester

### 2.1 Deployment and Hosting

The major achievement of the semester was preparing and moving the ViloCare system toward online hosting. Deployment support files were prepared specifically for InfinityFree hosting. The deployed system domain is:

`vilocare.infinityfree.io`

Deployment activities completed included:

1. Preparing the Laravel project structure for shared hosting.
2. Separating the application files from public web files for safer hosting.
3. Preparing a production environment configuration template.
4. Preparing the correct public entry file for InfinityFree hosting.
5. Preparing assets required in the public directory, including CSS, images, uploads, storage links, and manifests.

This deployment work is important because it moves the system from a development environment to an online-accessible platform.

### 2.2 Payment and Receipt Features

A new payment module was added to support accountability for selected activities in the system. The system now supports payment recording and receipt generation for:

1. EAC consultation activity
2. Patient result printing
3. Patient report or PDF download

The module captures details such as patient, service type, amount, currency, payment method, status, receipt number, reference, and payment date. This helps improve transparency and service documentation.

### 2.3 Email, SMS and AI Support

This semester introduced integrated communication services into the ViloCare platform.

#### 2.3.1 Email Services

The system supports email notifications for operational alerts and appointment reminders. This helps communication with responsible staff and improves follow-up processes.

#### 2.3.2 SMS Services

SMS functionality was introduced using an external messaging provider configuration. The SMS service supports:

1. Password reset by phone number
2. Appointment reminders
3. Alerts for important operational events

This makes the system more useful in environments where mobile communication is more practical than email.

#### 2.3.3 AI Chat Support

An AI-assisted dashboard chatbot was added to help users interact with dashboard summaries and administrative insights. The assistant was designed to support:

1. Dashboard metric interpretation
2. Report guidance
3. Workflow explanation
4. Administrative summaries

The AI assistant is intentionally limited from giving diagnosis or treatment advice, which is important for safe use in a health-related environment.

### 2.4 Security Improvements

Security was one of the main upgrade areas this semester. The following improvements were implemented.

#### 2.4.1 "I’m Not a Robot" Login Protection

Google reCAPTCHA was added to the login process to reduce unauthorized automated login attempts. This helps protect the system from bot-based attacks.

#### 2.4.2 Role-Based Access Control

The system now enforces user permissions according to roles such as:

1. Administrator
2. Clinician
3. Data Clerk
4. Lab Technician

This means employees without sufficient rights cannot delete or update sensitive entered information unless they are authorized super users such as administrators or clinicians.

#### 2.4.3 Forced Password Change on First Login

User registration was improved so that newly created users receive a system-generated default password. After login, the user is forced to change that password before continuing to use the system. This improves account security and accountability.

#### 2.4.4 Password Reset Improvements

The password reset process was extended to support both email-based and SMS-based recovery. This reduces the risk of account lockout while still maintaining controlled access.

### 2.5 Reports and Data Presentation

Reporting was significantly improved during the semester. The system can now generate and export reports in more practical formats, including PDF and Excel. Current report areas include:

1. Summary reports
2. Patient reports
3. Viral load reports

The reports also support filtering by time and location, making them more useful for monitoring and decision-making. Additional work was done to improve report authenticity and tracking through generated report references and verification features.

### 2.6 User and Workflow Maintenance Enhancements

Several maintenance-related improvements were also completed:

1. Better user management for account creation, editing, and controlled deletion
2. Appointment reminder support
3. Dashboard summaries for system monitoring
4. Profile management improvements
5. Better handling of patient results for printing and PDF download

These changes improve operational continuity and reduce the burden of manual follow-up.

## Chapter Three: Technical Summary of the Upgrade

The current semester upgrade was implemented on top of the Laravel-based ViloCare platform. The system now includes the following major functional areas:

1. Authentication and login protection
2. Password reset and first-login password change
3. Patient management
4. Viral load result management
5. EAC management
6. Appointments management
7. Payments and receipts
8. Report generation and export
9. Email, SMS, and AI support services
10. Role-restricted administration

The Laravel framework made it easier to organize routes, controllers, models, services, middleware, database migrations, and views in a more maintainable way. This semester's upgrades benefited from that structure by allowing new modules such as payment handling, deployment packaging, and chatbot support to be added more cleanly.

## Chapter Four: Achievements of the Semester

The major achievements registered this semester include the following:

1. Successful preparation of the ViloCare system for online hosting
2. Hosting under the InfinityFree platform
3. Addition of payment recording and receipt generation
4. Integration of email support
5. Integration of SMS support
6. Addition of an AI dashboard assistant
7. Introduction of anti-bot login protection
8. Stronger role-based restrictions on data modification
9. Improved reporting in PDF and Excel formats
10. System-generated default passwords with mandatory first password change

These achievements show clear progress from a framework migration project into a more practical and deployable healthcare system.

## Chapter Five: Challenges Encountered

During the upgrade process, several challenges were experienced:

1. Deploying a Laravel application on a free shared hosting platform required extra configuration compared to local development.
2. Integrating external services such as mail, SMS, reCAPTCHA, and AI required careful environment configuration and provider credentials.
3. Maintaining security while also keeping the system user friendly required balancing permissions and workflows.
4. Some live service integrations, especially telecom and stronger hosting security, are still incomplete.

These challenges were useful in exposing the practical realities of moving a classroom system toward an online production environment.

## Chapter Six: Pending Work

Despite the progress made, the following activities are still pending:

1. Getting the MTN API
2. Standardization of report forms with Ministry of Health logos
3. Making `vilocare.infinityfree.io` more secure
4. Modernizing the AI chatbot

These pending items form the basis for the next phase of system improvement.

## Chapter Seven: Conclusion

The Year 3 Semester 2 ViloCare upgrade achieved its major goal of moving the project beyond framework migration into deployment, service integration, security improvement, and operational maintenance. The most significant accomplishment was preparing and hosting the system online using InfinityFree. In addition, important practical features were added, including payments and receipts, mail and SMS services, AI-assisted support, login protection, role-based permissions, and stronger password handling.

Although some important items are still pending, the progress made this semester demonstrates that the ViloCare system is becoming more mature, more secure, and more suitable for real-world use. The project has therefore advanced meaningfully from an academic prototype toward a deployable health information support system.

## Recommendations

The following recommendations are proposed for the next phase of the project:

1. Complete MTN API integration to support stronger telecom-based workflows.
2. Add official Ministry of Health branding and standardized forms to all reports.
3. Strengthen production security through secure hosting configuration, HTTPS enforcement, secret rotation, and deployment hardening.
4. Improve the AI chatbot with a more modern interface and more context-aware administrative support.
5. Continue testing the live deployment to improve performance, reliability, and maintainability.

## Appendix A: Summary of Major Improvements

| Area | Improvement Made |
| --- | --- |
| Framework Base | Continued work on the Laravel-based system migrated last semester |
| Deployment | Prepared and hosted online using InfinityFree |
| Domain | `vilocare.infinityfree.io` |
| Security | reCAPTCHA, role-based access, password reset, forced password change |
| Communication | Email services, SMS services, AI chat assistant |
| Payments | Added payment capture and receipt generation |
| Reporting | PDF and Excel report generation with filters and verification |
| Maintenance | User management, reminders, profile updates, workflow support |

## Appendix B: Suggested Template Notes

If this report is to be transferred into the school project template, the following can be added or adjusted easily:

1. University cover page design
2. Declaration
3. Approval page
4. Dedication
5. Acknowledgement
6. Table of contents
7. List of figures and tables, where required

