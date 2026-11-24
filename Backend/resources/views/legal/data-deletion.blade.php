@extends('layouts.app')

@section('title', 'User Data Deletion Instructions - Rumu App')

@section('content')
    <div class="min-h-screen gradient-bg py-8 sm:py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto animate-fade-in">
            <div class="text-center mb-8">
                <a href="{{ route('login') }}" class="inline-block">
                    <img src="https://iconicpages.com/wp-content/uploads/2025/12/rumu-1.png" alt="RUMU Logo" class="h-16 mx-auto mb-4">
                </a>
                <h1 class="text-2xl sm:text-3xl font-bold text-white">User Data Deletion Instructions</h1>
                <p class="text-white/70 text-sm mt-2">Last updated: November 18, 2026</p>
            </div>

            <div class="glass-effect rounded-2xl shadow-2xl p-6 sm:p-10 text-white/90 text-sm sm:text-base leading-relaxed space-y-6">
                <p>
                    Rumu provides CRM solutions with integrations for Meta (Facebook, Instagram & WhatsApp).
                    In compliance with the Meta Platform Terms, GDPR, and Indian DPDP Act, we respect your right to control
                    your personal information and connected social data. Below are instructions on how to request the deletion of your data.
                </p>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">1. Deleting Your Data via Facebook App Settings</h2>
                    <p class="mb-2">If you have connected your Facebook Page or Instagram Business profile to Rumu, you can revoke access and trigger data removal at any time directly through Facebook:</p>
                    <ol class="list-decimal list-inside space-y-2 text-white/80">
                        <li>Log in to your Facebook account and go to <strong>Settings &amp; Privacy &rarr; Settings</strong>.</li>
                        <li>In the left sidebar, click on <strong>Apps and Websites</strong>.</li>
                        <li>Locate <strong>Rumu</strong> in your list of active apps and click <strong>Remove</strong>.</li>
                        <li>Check the box to delete posts, videos, or events if prompted, and confirm.</li>
                    </ol>
                    <p class="mt-2 text-sm text-white/70">
                        Once removed, Facebook notifies Rumu and all Page access tokens are immediately revoked and deactivated.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">2. In-App Account Deletion</h2>
                    <p>
                        You can also permanently delete your CRM account and all associated records directly from within the Rumu application:
                    </p>
                    <ul class="list-disc list-inside space-y-1 text-white/80 mt-2">
                        <li>Sign in to your account at <a href="https://app.rumu.in" class="text-secondary hover:text-white underline">app.rumu.in</a>.</li>
                        <li>Go to <strong>Settings &rarr; Account Settings</strong>.</li>
                        <li>Click <strong>Delete Account</strong> to permanently purge your account data.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">3. Manual Data Deletion Request via Email</h2>
                    <p>
                        If you prefer to submit a manual deletion request, you can email our Data Protection team:
                    </p>
                    <p class="mt-2 text-white/80">
                        Email: <a href="mailto:support@rumu.in" class="text-secondary hover:text-white underline font-semibold">support@rumu.in</a> or <a href="mailto:billing@rumu.in" class="text-secondary hover:text-white underline font-semibold">billing@rumu.in</a><br>
                        Subject: <em>Data Deletion Request</em><br>
                        Please include your registered email address and your connected Facebook Page / Instagram handle.
                    </p>
                    <p class="mt-2 text-sm text-white/70">
                        We will confirm your identity and process the deletion within <strong>48 hours</strong>.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">4. What Data is Deleted?</h2>
                    <ul class="list-disc list-inside space-y-1 text-white/80">
                        <li>Meta access tokens, user credentials, and session tokens.</li>
                        <li>Connected page data, Instagram profiles, synced chats, and message history.</li>
                        <li>Lead forms, customer contacts, notes, and activity logs.</li>
                        <li>Account profile information (name, email, phone number).</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">5. Contact Information</h2>
                    <p>
                        If you have questions regarding this Data Deletion policy, please contact:<br>
                        <strong>Rumu Support</strong><br>
                        Email: <a href="mailto:support@rumu.in" class="text-secondary hover:text-white underline">support@rumu.in</a><br>
                        URL: <a href="https://app.rumu.in" class="text-secondary hover:text-white underline">https://app.rumu.in</a>
                    </p>
                </section>

                <div class="pt-4 border-t border-white/20 flex flex-wrap gap-4 text-sm">
                    <a href="{{ route('privacy-policy') }}" class="text-secondary hover:text-white font-medium transition">Privacy Policy</a>
                    <a href="{{ route('terms-of-service') }}" class="text-secondary hover:text-white font-medium transition">Terms of Service</a>
                    <a href="{{ route('login') }}" class="text-secondary hover:text-white font-medium transition">Back to Login</a>
                </div>
            </div>

            <p class="text-center text-white/70 text-sm mt-6">© 2026 Rumu App. All rights reserved.</p>
        </div>
    </div>
@endsection
