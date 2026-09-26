@extends('layouts.app')
@section('title', 'Privacy Policy | Student Desk Application')

@section('content')

    @push('styles')
        <style>
            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family:
                    -apple-system,
                    BlinkMacSystemFont,
                    "Segoe UI",
                    Roboto,
                    Helvetica,
                    Arial,
                    sans-serif;

                background: #f5f7fa;
                color: #1f2937;
                line-height: 1.7;
            }

            .privacy-container {
                width: min(100% - 32px, 900px);
                margin: 40px auto;
            }

            .privacy-card {
                background: #ffffff;
                border-radius: 14px;
                padding: 40px;
                box-shadow:
                    0 4px 20px rgba(0, 0, 0, 0.06);
            }

            .privacy-header {
                border-bottom: 1px solid #e5e7eb;
                padding-bottom: 25px;
                margin-bottom: 30px;
            }

            .privacy-header h1 {
                margin: 0 0 8px;
                font-size: 32px;
                color: #111827;
            }

            .privacy-header p {
                margin: 0;
                color: #6b7280;
            }

            .privacy-section {
                margin-bottom: 30px;
            }

            .privacy-section h2 {
                margin: 0 0 12px;
                font-size: 21px;
                color: #111827;
            }

            .privacy-section h3 {
                margin: 20px 0 8px;
                font-size: 17px;
                color: #374151;
            }

            .privacy-section p {
                margin: 0 0 12px;
            }

            .privacy-section ul {
                margin: 8px 0 15px;
                padding-left: 25px;
            }

            .privacy-section li {
                margin-bottom: 7px;
            }

            .privacy-highlight {
                background: #f3f4f6;
                border-left: 4px solid #2563eb;
                padding: 15px 18px;
                margin: 15px 0;
                border-radius: 4px;
            }

            .privacy-footer {
                border-top: 1px solid #e5e7eb;
                margin-top: 35px;
                padding-top: 20px;
                color: #6b7280;
                font-size: 14px;
            }

            a {
                color: #2563eb;
            }

            @media (max-width: 600px) {

                .privacy-container {
                    width: min(100% - 20px, 900px);
                    margin: 20px auto;
                }

                .privacy-card {
                    padding: 24px 20px;
                }

                .privacy-header h1 {
                    font-size: 26px;
                }

                .privacy-section h2 {
                    font-size: 19px;
                }
            }
        </style>
    @endpush

@section('title', 'Privacy Policy | The Future University')

@section('content')
    <div class="privacy-container">

        <article class="privacy-card" style="background-color: #ffeceb; ">

            <header class="privacy-header">

                <h1>
                    Privacy Policy
                </h1>

                <p>
                    Student Desk Application
                </p>

                <p>
                    Last updated:
                    September 26, 2026
                </p>

            </header>


            <!-- 1 -->

            <section class="privacy-section">

                <h2>1. Introduction</h2>

                <p>
                    This Privacy Policy explains how the Student Desk
                    Application ("the Application", "we", "us", or
                    "our") collects, uses, stores, and protects information
                    when students use the Application.
                </p>

                <p>
                    The Application is designed to provide students with
                    access to university-related academic and administrative
                    information through a mobile application.
                </p>

            </section>


            <!-- 2 -->

            <section class="privacy-section">

                <h2>2. Information We Collect</h2>

                <p>
                    Depending on how the Application is used, we may process
                    the following categories of information:
                </p>

                <h3>Student Information</h3>

                <ul>
                    <li>Student identification number.</li>
                    <li>Student name.</li>
                    <li>University email address, where applicable.</li>
                    <li>Faculty and major information.</li>
                    <li>Academic batch and semester information.</li>
                </ul>

                <h3>Academic Information</h3>

                <ul>
                    <li>Semester results.</li>
                    <li>Course information.</li>
                    <li>Class timetable information.</li>
                    <li>Academic status and related university information.</li>
                </ul>

                <h3>Financial Information</h3>

                <p>
                    Where supported by the Application and university systems,
                    students may be shown information related to university
                    fees and payment records.
                </p>

                <p>
                    The Application does not require students to enter
                    complete payment-card information into the Application
                    unless such functionality is explicitly introduced and
                    described separately.
                </p>

                <h3>Device and Technical Information</h3>

                <p>
                    The Application may process limited technical information
                    required to provide and secure its services, including
                    device identifiers and push-notification information.
                </p>

            </section>


            <!-- 3 -->

            <section class="privacy-section">

                <h2>3. How We Use Information</h2>

                <p>
                    Information processed through Student Desk Application may be used to:
                </p>

                <ul>
                    <li>Authenticate students.</li>
                    <li>Provide access to student accounts.</li>
                    <li>Display academic information.</li>
                    <li>Display semester results.</li>
                    <li>Display fee information.</li>
                    <li>Display class timetables.</li>
                    <li>Send important university notifications.</li>
                    <li>Maintain application security.</li>
                    <li>Diagnose technical problems.</li>
                    <li>Improve reliability and functionality.</li>
                </ul>

            </section>


            <!-- 4 -->

            <section class="privacy-section">

                <h2>4. Notifications</h2>

                <p>
                    Student Desk Application may use push-notification services to deliver
                    university-related messages and other notifications
                    relevant to students.
                </p>

                <p>
                    Students may be able to control notification permissions
                    through their device settings and, where supported,
                    through application settings.
                </p>

            </section>


            <!-- 5 -->

            <section class="privacy-section">

                <h2>5. How Information Is Shared</h2>

                <p>
                    Student information is used primarily to provide the
                    services of the Application.
                </p>

                <div class="privacy-highlight">

                    We do not sell students' personal information to third
                    parties.

                </div>

                <p>
                    Information may be processed by service providers that
                    are necessary to operate the Application, such as hosting,
                    authentication, notification, database, or infrastructure
                    providers.
                </p>

                <p>
                    Such processing is limited to what is necessary for
                    providing and maintaining the Application and its
                    services.
                </p>

            </section>


            <!-- 6 -->

            <section class="privacy-section">

                <h2>6. Data Security</h2>

                <p>
                    We take reasonable technical and organizational measures
                    to protect information processed through the Application
                    against unauthorized access, alteration, disclosure, or
                    destruction.
                </p>

                <p>
                    However, no method of electronic transmission or storage
                    can be guaranteed to be completely secure.
                </p>

            </section>


            <!-- 7 -->

            <section class="privacy-section">

                <h2>7. Data Retention</h2>

                <p>
                    Student information may be retained for as long as
                    necessary to provide university services, comply with
                    applicable university requirements, maintain academic
                    records, resolve disputes, and satisfy applicable legal
                    obligations.
                </p>

            </section>


            <!-- 8 -->

            <section class="privacy-section">

                <h2>8. Student Rights and Requests</h2>

                <p>
                    Students may contact the university regarding questions
                    about their personal information or to request
                    clarification concerning the information associated with
                    their account.
                </p>

                <p>
                    Requests may be subject to identity verification and
                    applicable university policies and legal requirements.
                </p>

            </section>


            <!-- 9 -->

            <section class="privacy-section">

                <h2>9. Children's Privacy</h2>

                <p>
                    The Application is intended for university students and
                    is not specifically directed toward children.
                </p>

                <p>
                    We do not knowingly collect personal information from
                    children through the Application for purposes unrelated
                    to providing university services.
                </p>

            </section>


            <!-- 10 -->

            <section class="privacy-section">

                <h2>10. Third-Party Services</h2>

                <p>
                    The Application may rely on third-party services required
                    for functionality such as push notifications, hosting,
                    authentication, analytics, or infrastructure.
                </p>

                <p>
                    These services may process limited technical or account
                    information according to their respective privacy policies
                    and terms.
                </p>

            </section>


            <!-- 11 -->

            <section class="privacy-section">

                <h2>11. Changes to This Privacy Policy</h2>

                <p>
                    We may update this Privacy Policy from time to time to
                    reflect changes to the Application, university services,
                    applicable requirements, or data-processing practices.
                </p>

                <p>
                    When changes are made, the updated version will be
                    published on this page with a revised "Last updated"
                    date.
                </p>

            </section>


            <!-- 12 -->

            <section class="privacy-section">

                <h2>12. Contact Us</h2>

                <p>
                    If you have questions or concerns about this Privacy
                    Policy or the handling of your information, please contact
                    the university through its official support channels.
                </p>

                <p>
                    <strong>Organization:</strong>
                    Future University
                </p>

                <p>
                    <strong>Application:</strong>
                    Student Desk Application
                </p>

                <p>
                    <strong>Email:</strong>
                    <a href="mailto:cesd@fu.edu.sd">
                        cesd@fu.edu.sd
                    </a>
                </p>

            </section>

        </article>

    </div>
@endsection
