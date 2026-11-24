import { LegalLayout } from './LegalLayout'

export const PrivacyPolicyPage = () => (
  <LegalLayout title="Privacy Policy" updatedAt="9 November 2026">
    <section>
      <h2>1. Information we collect</h2>
      <p className="mt-2">
        When you create a Rumu account we collect your name, email address and, if you provide them,
        your phone number, company details and profile photo. When you use the workspace we also store
        the campaigns, leads, notes and inventory records you create.
      </p>
    </section>
    <section>
      <h2>2. How we use information</h2>
      <p className="mt-2">
        We use your information to operate your account, deliver the features you request, process
        payments, send service and billing notifications, and keep the platform secure. Lead data you
        upload remains under your control and is used only to provide the service to you.
      </p>
    </section>
    <section>
      <h2>3. Sharing</h2>
      <p className="mt-2">
        We share data with service providers that help us run Rumu, such as payment processing and
        cloud hosting providers. We do not sell personal information. When you connect Meta or a social
        account, information is exchanged with that provider according to the permissions you grant.
      </p>
      <h3 className="mt-4 font-semibold">AI clients and Rumu MCP</h3>
      <p className="mt-2">
        You may optionally connect an AI client such as ChatGPT or Codex to Rumu through OAuth 2.0.
        When you explicitly ask the connected client to retrieve or update information, the relevant
        CRM data (which may include leads, notes, campaigns, and related records) is transmitted over
        HTTPS/TLS to fulfill that request and may be returned to the AI client for its response. The
        AI provider processes prompts and results under its own terms and privacy policy.
      </p>
      <p className="mt-2">
        Each connection uses an access token scoped for the Rumu MCP integration. Rumu stores the token
        securely and it expires after 30 days. You can revoke active Rumu tokens, including MCP access,
        at any time by choosing “Sign out everywhere” from your account menu; this also signs you out
        of your other active sessions. The gateway's supplemental audit log records the tool name, time,
        outcome, a hash of the tool arguments, and an affected record ID when applicable. It does not
        store raw CRM content or access-token values. Its configured retention period is seven days by
        default.
      </p>
      <p className="mt-2">
        Rumu does not sell customer CRM data or use it to train AI models.
      </p>
    </section>
    <section>
      <h2>4. Cookies and local storage</h2>
      <p className="mt-2">
        Rumu uses browser storage to keep you signed in and to remember your theme preference. We do not
        use advertising cookies in the application workspace.
      </p>
    </section>
    <section>
      <h2>5. Data retention and security</h2>
      <p className="mt-2">
        We retain account data while your account is active and for a reasonable period afterwards to
        meet legal obligations. We apply access controls, encryption in transit and least-privilege
        access to protect your data.
      </p>
    </section>
    <section>
      <h2>6. Your choices</h2>
      <p className="mt-2">
        You can update your profile, notification preferences and appearance from Settings. You may
        request a copy of your data or deletion of your account by contacting our support team. Some
        records may be retained where required by law, such as tax invoices.
      </p>
    </section>
    <section>
      <h2>7. Contact</h2>
      <p className="mt-2">
        For privacy questions, contact us at{' '}
        <a href="mailto:privacy@rumu.in" className="font-medium text-highlight hover:underline">
          privacy@rumu.in
        </a>
        .
      </p>
    </section>
  </LegalLayout>
)
