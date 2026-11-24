@extends('layouts.app')

@section('title', 'Terms of Service - Rumu App')

@section('content')
    <div class="min-h-screen gradient-bg py-8 sm:py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto animate-fade-in">
            <div class="text-center mb-8">
                <a href="{{ route('login') }}" class="inline-block">
                    <img src="https://iconicpages.com/wp-content/uploads/2025/12/rumu-1.png" alt="RUMU Logo" class="h-16 mx-auto mb-4">
                </a>
                <h1 class="text-2xl sm:text-3xl font-bold text-white">Terms of Service</h1>
                <p class="text-white/70 text-sm mt-2">Last updated: June 17, 2026</p>
            </div>

            <div class="glass-effect rounded-2xl shadow-2xl p-6 sm:p-10 text-white/90 text-sm sm:text-base leading-relaxed space-y-6">
                <p>
                    By accessing or using the Rumu website, services, platforms, consultations, marketing solutions, or any
                    related offerings, you agree to be bound by these Terms of Service. If you do not agree with any part of
                    these terms, you should discontinue use of our website and services immediately.
                </p>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">Services</h2>
                    <p>
                        Rumu provides performance marketing, lead generation, advertising, consulting, creative, technology,
                        and related business services primarily for real estate developers, brokers, channel partners, and
                        businesses seeking marketing support. All information provided on our website is for general
                        informational and business purposes only and should not be considered legal, financial, investment,
                        or professional advice. Users are responsible for evaluating the suitability of our services for
                        their specific requirements.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">User Information</h2>
                    <p>
                        By submitting inquiries, forms, applications, or other information through our website, you represent
                        and warrant that all information provided is accurate, complete, and up to date. You agree not to
                        provide false, misleading, fraudulent, or unauthorized information and acknowledge that Rumu may rely
                        on the information provided in delivering services and communications.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">Acceptable Use</h2>
                    <p>
                        Users agree to use the website and services only for lawful purposes and in a manner that does not
                        violate any applicable laws, regulations, or the rights of others. You may not attempt to gain
                        unauthorized access to any portion of the website, interfere with its operation, introduce malicious
                        software, engage in data scraping, reverse engineer any part of the platform, or use the website in
                        any way that could damage, disable, overburden, or impair its functionality.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">Third-Party Services</h2>
                    <p>
                        Rumu may use third-party software, hosting providers, analytics services, advertising platforms, CRM
                        systems, and technology partners to deliver its services. We are not responsible for interruptions,
                        delays, outages, inaccuracies, or failures caused by third-party systems, internet service providers,
                        external platforms, or circumstances beyond our reasonable control.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">No Guaranteed Results</h2>
                    <p>
                        While we strive to provide effective marketing and business solutions, Rumu does not guarantee specific
                        business outcomes, sales, revenue growth, lead volume, conversion rates, return on investment, project
                        bookings, or any other commercial results unless expressly agreed upon in a separate written agreement.
                        Marketing performance can be affected by numerous factors outside our control, including market
                        conditions, competition, pricing, sales processes, customer behavior, and regulatory changes.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">Intellectual Property</h2>
                    <p>
                        All content on the website, including but not limited to text, graphics, logos, branding, designs,
                        images, videos, software, layouts, and other materials, is owned by or licensed to Rumu and is
                        protected by applicable intellectual property laws. Users may not copy, reproduce, distribute, modify,
                        publish, transmit, create derivative works from, or commercially exploit any content without prior
                        written permission from Rumu.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">Limitation of Liability</h2>
                    <p>
                        To the maximum extent permitted by applicable law, Rumu shall not be liable for any direct, indirect,
                        incidental, consequential, special, punitive, or exemplary damages arising out of or related to the
                        use of, inability to use, reliance upon, or performance of the website, services, content, advertising
                        campaigns, technology platforms, or third-party services, even if advised of the possibility of such
                        damages. Users agree that their use of the website and services is at their own risk.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">Indemnification</h2>
                    <p>
                        Users agree to indemnify, defend, and hold harmless Rumu, its directors, officers, employees,
                        affiliates, contractors, partners, and representatives from and against any claims, losses, liabilities,
                        damages, costs, expenses, or legal fees arising from their use of the website or services, violation of
                        these Terms, infringement of any rights, or violation of any applicable law.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">Modifications</h2>
                    <p>
                        Rumu reserves the right to modify, suspend, discontinue, restrict access to, or remove any part of the
                        website, services, content, or features at any time without prior notice. We also reserve the right to
                        update these Terms of Service periodically. Continued use of the website or services after any changes
                        become effective constitutes acceptance of the revised Terms.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">Severability and Waiver</h2>
                    <p>
                        If any provision of these Terms is determined to be invalid, unlawful, or unenforceable, the remaining
                        provisions shall remain in full force and effect. The failure of Rumu to enforce any provision of these
                        Terms shall not constitute a waiver of that provision or any other rights.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">Governing Law</h2>
                    <p>
                        These Terms of Service shall be governed by and construed in accordance with the laws of India. Any
                        disputes arising from or relating to these Terms, the website, or the services shall be subject to the
                        exclusive jurisdiction of the courts located in Pune, Maharashtra, India.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">Acceptance</h2>
                    <p>
                        By accessing or using the Rumu website and services, you acknowledge that you have read, understood,
                        and agreed to be bound by these Terms of Service. For any questions regarding these Terms, users may
                        contact Rumu through the contact information provided on the website.
                    </p>
                </section>

                <div class="pt-4 border-t border-white/20 flex flex-wrap gap-4 text-sm">
                    <a href="{{ route('privacy-policy') }}" class="text-secondary hover:text-white font-medium transition">Privacy Policy</a>
                    <a href="{{ route('login') }}" class="text-secondary hover:text-white font-medium transition">Back to Login</a>
                    <a href="{{ route('register') }}" class="text-secondary hover:text-white font-medium transition">Register</a>
                </div>
            </div>

            <p class="text-center text-white/70 text-sm mt-6">© 2026 Rumu App. All rights reserved.</p>
        </div>
    </div>
@endsection
