import { LegalLayout } from './LegalLayout'

export const DataDeletionPage = () => (
  <LegalLayout title="User Data Deletion Instructions" updatedAt="18 November 2026">
    <section>
      <h2>Overview</h2>
      <p className="mt-2">
        Rumu provides a CRM platform with Meta (Facebook, Instagram & WhatsApp) integrations.
        In compliance with Meta Platform Policies and global data privacy standards (GDPR, CCPA, and Indian DPDP Act),
        we provide users with complete control over their personal and connected data, including the right to request
        immediate deletion of all data associated with their account.
      </p>
    </section>

    <section>
      <h2>How to Delete Your Data via Facebook Account Settings</h2>
      <p className="mt-2">
        If you connected your Facebook Page or Instagram Business Account to Rumu and wish to remove Rumu's access and delete your data, follow these steps:
      </p>
      <ol className="mt-3 space-y-2 list-decimal list-inside text-muted-foreground">
        <li>Log in to your Facebook profile and navigate to <strong>Settings &amp; Privacy &rarr; Settings</strong>.</li>
        <li>In the left sidebar, click on <strong>Apps and Websites</strong>.</li>
        <li>Find <strong>Rumu</strong> (or Rumu App) in the list of connected applications.</li>
        <li>Click the <strong>Remove</strong> button next to Rumu.</li>
        <li>
          Optionally check the box: <em>&ldquo;Delete posts, videos or events Rumu posted on your timeline&rdquo;</em> and confirm removal.
        </li>
      </ol>
      <p className="mt-3">
        Once removed, Meta automatically informs our systems and revokes all Page and Instagram access tokens immediately.
      </p>
    </section>

    <section>
      <h2>How to Request Manual Account &amp; Data Deletion in Rumu</h2>
      <p className="mt-2">
        You can request complete deletion of your Rumu CRM workspace, connected social accounts, leads, campaigns, and comments at any time:
      </p>
      <div className="mt-3 space-y-3">
        <div className="rounded-xl border border-border p-4 bg-card">
          <h3 className="text-sm font-semibold text-foreground">Option 1: From Your Rumu Settings</h3>
          <p className="mt-1 text-xs text-muted-foreground">
            Sign in to your Rumu dashboard &rarr; Go to <strong>Settings</strong> &rarr; Navigate to <strong>Account Settings</strong> &rarr; Click <strong>Delete Account</strong>. This permanently purges your account, campaigns, leads, and connected social pages.
          </p>
        </div>
        <div className="rounded-xl border border-border p-4 bg-card">
          <h3 className="text-sm font-semibold text-foreground">Option 2: Email Our Data Protection Officer</h3>
          <p className="mt-1 text-xs text-muted-foreground">
            Send an email to <a href="mailto:support@rumu.in" className="text-highlight underline font-medium">support@rumu.in</a> or <a href="mailto:billing@rumu.in" className="text-highlight underline font-medium">billing@rumu.in</a> with the subject line <em>&ldquo;Data Deletion Request&rdquo;</em> including your registered email address and Meta Page ID.
          </p>
        </div>
      </div>
    </section>

    <section>
      <h2>What Data Gets Deleted?</h2>
      <p className="mt-2">Upon processing your data deletion request:</p>
      <ul className="mt-2 space-y-1 list-disc list-inside text-muted-foreground">
        <li>All Meta Graph API access tokens and refresh tokens are permanently purged.</li>
        <li>Connected Facebook Pages and Instagram accounts are disconnected and removed.</li>
        <li>Stored social conversations, direct messages, comments, and post sync logs are permanently deleted.</li>
        <li>Lead form submissions, customer contacts, and notes are erased from our database.</li>
        <li>User profile details (name, email, profile photo) are completely removed.</li>
      </ul>
    </section>

    <section>
      <h2>Processing Timeline</h2>
      <p className="mt-2">
        Automated revocations via Meta Apps &amp; Websites take effect immediately. Manual email deletion requests are processed and confirmed via email within <strong>48 hours</strong>.
      </p>
    </section>

    <section>
      <h2>Contact Us</h2>
      <p className="mt-2">
        For any privacy or data deletion inquiries, please reach out to:
        <br />
        <strong>Rumu Support Team</strong>
        <br />
        Email: <a href="mailto:support@rumu.in" className="text-highlight underline">support@rumu.in</a> / <a href="mailto:billing@rumu.in" className="text-highlight underline">billing@rumu.in</a>
        <br />
        Website: <a href="https://app.rumu.in" className="text-highlight underline">https://app.rumu.in</a>
      </p>
    </section>
  </LegalLayout>
)
