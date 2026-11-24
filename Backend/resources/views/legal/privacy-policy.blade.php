@extends('layouts.app')

@section('title', 'Privacy Policy - Rumu App')

@section('content')
    <div class="min-h-screen gradient-bg py-8 sm:py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto animate-fade-in">
            <div class="text-center mb-8">
                <a href="{{ route('login') }}" class="inline-block">
                    <img src="https://iconicpages.com/wp-content/uploads/2025/12/rumu-1.png" alt="RUMU Logo" class="h-16 mx-auto mb-4">
                </a>
                <h1 class="text-2xl sm:text-3xl font-bold text-white">Privacy Policy</h1>
                <p class="text-white/70 text-sm mt-2">Last updated: November 9, 2026</p>
            </div>

            <div class="glass-effect rounded-2xl shadow-2xl p-6 sm:p-10 text-white/90 text-sm sm:text-base leading-relaxed space-y-6">
                <p>
                    Rumu ("we," "us," or "our") respects your privacy and is committed to protecting the personal information
                    you share with us when you access our website, CRM platform, marketing services, or related offerings.
                    This Privacy Policy explains how we collect, use, disclose, and safeguard your information.
                </p>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">1. Information We Collect</h2>
                    <p class="mb-2">We may collect the following types of information:</p>
                    <ul class="list-disc list-inside space-y-1 text-white/80">
                        <li><strong class="text-white">Account information:</strong> name, email address, phone number, password (stored securely), profile photo, company details, and role within the platform.</li>
                        <li><strong class="text-white">Social login data:</strong> if you sign in or link Google or Facebook, we may receive your name, email, profile picture, and platform-specific identifiers from those providers.</li>
                        <li><strong class="text-white">Business and lead data:</strong> campaign details, lead records, notes, import files, and CRM activity you create or manage within the platform.</li>
                        <li><strong class="text-white">Marketing and integration data:</strong> information related to Meta Lead Ads, advertising accounts, page connections, and conversion events where you authorize such integrations.</li>
                        <li><strong class="text-white">Technical data:</strong> IP address, browser type, device information, session data, log files, and usage patterns on our website and application.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">2. How We Use Your Information</h2>
                    <p class="mb-2">We use collected information to:</p>
                    <ul class="list-disc list-inside space-y-1 text-white/80">
                        <li>Provide, operate, and maintain our website, CRM platform, and related services.</li>
                        <li>Create and manage user accounts, authenticate logins, and enable social account linking.</li>
                        <li>Process campaigns, leads, imports, notifications, and subscription or plan-related features.</li>
                        <li>Deliver marketing, advertising, consulting, and technology services you request from Rumu.</li>
                        <li>Communicate with you regarding your account, support requests, service updates, and business inquiries.</li>
                        <li>Improve platform performance, security, analytics, and user experience.</li>
                        <li>Comply with legal obligations and enforce our Terms of Service.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">3. Third-Party Services</h2>
                    <p>
                        Rumu may use third-party software, hosting providers, analytics services, advertising platforms,
                        CRM systems, authentication providers, and technology partners to deliver its services. These may
                        include Google OAuth, Facebook Login, Meta advertising and lead tools, cloud hosting, email delivery,
                        and payment or subscription processors where applicable.
                    </p>
                    <p class="mt-2">
                        Information shared with third parties is limited to what is necessary to provide the relevant service.
                        Third-party providers process data under their own privacy policies and terms. We are not responsible
                        for the privacy practices of external platforms beyond our reasonable control.
                    </p>
                    <h3 class="text-base font-semibold text-white mt-4 mb-2">AI Clients and Rumu MCP</h3>
                    <p>
                        You may optionally connect an AI client such as ChatGPT or Codex to Rumu through OAuth 2.0.
                        When you explicitly ask the connected client to retrieve or update information, the relevant CRM data,
                        which may include leads, notes, campaigns, and related records, is transmitted over HTTPS/TLS to fulfill
                        that request and may be returned to the AI client for its response. The AI provider processes prompts
                        and results under its own terms and privacy policy.
                    </p>
                    <p class="mt-2">
                        Each connection uses an access token scoped for the Rumu MCP integration. Rumu stores the token securely;
                        it expires after 30 days. You can revoke active Rumu tokens, including MCP access, at any time by choosing
                        “Sign out everywhere” from your account menu; this also signs you out of your other active sessions.
                        The gateway's supplemental audit log records the tool name, time, outcome, a hash of the tool arguments,
                        and an affected record ID when applicable. It does not store raw CRM content or access-token values. Its
                        configured retention period is seven days by default. Rumu does not sell customer CRM data or use it to
                        train AI models.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">4. Cookies and Similar Technologies</h2>
                    <p>
                        We use cookies, session storage, and similar technologies to maintain secure logins, remember preferences,
                        support platform functionality, and understand how users interact with our services. You may control
                        cookie settings through your browser, though disabling certain cookies may affect platform functionality.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">5. Data Sharing and Disclosure</h2>
                    <p class="mb-2">We do not sell your personal information. We may share information:</p>
                    <ul class="list-disc list-inside space-y-1 text-white/80">
                        <li>With service providers and partners who assist in operating our platform and delivering services.</li>
                        <li>With advertising and analytics platforms you connect to your account, such as Meta, when you authorize integrations.</li>
                        <li>With administrators or authorized users within your organization or account structure, where applicable.</li>
                        <li>When required by law, regulation, legal process, or to protect the rights, safety, and security of Rumu, users, or others.</li>
                        <li>In connection with a merger, acquisition, restructuring, or sale of assets, subject to applicable law.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">6. Data Retention</h2>
                    <p>
                        We retain personal and business information for as long as necessary to provide services, fulfill the
                        purposes described in this policy, comply with legal obligations, resolve disputes, and enforce our
                        agreements. Retention periods may vary depending on the type of data and your relationship with Rumu.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">7. Data Security</h2>
                    <p>
                        We implement reasonable administrative, technical, and organizational measures designed to protect your
                        information against unauthorized access, loss, misuse, or alteration. However, no method of transmission
                        or storage is completely secure, and we cannot guarantee absolute security.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">8. Your Rights and Choices</h2>
                    <p class="mb-2">Depending on applicable law, you may have the right to:</p>
                    <ul class="list-disc list-inside space-y-1 text-white/80">
                        <li>Access, update, or correct your account information through your profile settings.</li>
                        <li>Request deletion of your account or certain personal data, subject to legal and operational requirements.</li>
                        <li>Withdraw consent for optional communications or certain processing activities where applicable.</li>
                        <li>Unlink connected Google or Facebook accounts from your profile.</li>
                        <li>Contact us regarding privacy-related questions or requests.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">9. Children's Privacy</h2>
                    <p>
                        Our services are intended for business users and are not directed to individuals under the age of 18.
                        We do not knowingly collect personal information from children. If you believe a child has provided us
                        personal information, please contact us so we can take appropriate action.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">10. International and Cross-Border Processing</h2>
                    <p>
                        Your information may be processed or stored on servers located in India or other jurisdictions where
                        our service providers operate. By using our services, you acknowledge that your information may be
                        transferred and processed in accordance with this Privacy Policy and applicable law.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">11. Changes to This Policy</h2>
                    <p>
                        Rumu reserves the right to update this Privacy Policy from time to time. Changes will be posted on this
                        page with an updated effective date. Continued use of our website or services after changes become
                        effective constitutes acceptance of the revised policy.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">12. Governing Law</h2>
                    <p>
                        This Privacy Policy shall be governed by and construed in accordance with the laws of India. Any disputes
                        arising from or relating to this policy shall be subject to the exclusive jurisdiction of the courts
                        located in Pune, Maharashtra, India.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">13. Contact Us</h2>
                    <p>
                        If you have questions about this Privacy Policy or how we handle your information, please contact Rumu
                        through the contact information provided on our website at
                        <a href="https://app.rumu.in" class="text-secondary hover:text-white underline">app.rumu.in</a>.
                    </p>
                </section>

                <div class="pt-4 border-t border-white/20 flex flex-wrap gap-4 text-sm">
                    <a href="{{ route('terms-of-service') }}" class="text-secondary hover:text-white font-medium transition">Terms of Service</a>
                    <a href="{{ route('login') }}" class="text-secondary hover:text-white font-medium transition">Back to Login</a>
                    <a href="{{ route('register') }}" class="text-secondary hover:text-white font-medium transition">Register</a>
                </div>
            </div>

            <p class="text-center text-white/70 text-sm mt-6">© 2026 Rumu App. All rights reserved.</p>
        </div>
    </div>
@endsection
