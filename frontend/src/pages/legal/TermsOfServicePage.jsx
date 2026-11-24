import { LegalLayout } from './LegalLayout'

export const TermsOfServicePage = () => (
  <LegalLayout title="Terms of Service" updatedAt="25 October 2026">
    <section>
      <h2>1. Using Rumu</h2>
      <p className="mt-2">
        Rumu is a real estate CRM for realtors and their teams. You must be at least 18 years old and
        authorised to act on behalf of any organisation you register. You are responsible for the
        accuracy of your account details and for activity under your credentials.
      </p>
    </section>
    <section>
      <h2>2. Your content and leads</h2>
      <p className="mt-2">
        You retain ownership of the leads, notes and inventory you add. You confirm that you have the
        necessary consent to store and contact the individuals whose details you upload, and that your
        outreach complies with applicable marketing and data-protection laws.
      </p>
    </section>
    <section>
      <h2>3. Subscriptions and billing</h2>
      <p className="mt-2">
        Paid plans are billed for the period you select at checkout through our payment provider.
        Fees are exclusive of applicable taxes unless stated otherwise. Plan limits, such as campaign
        and lead allowances, apply as displayed at purchase. Suspended or expired subscriptions may
        restrict access to campaign features while account and billing pages remain available.
      </p>
    </section>
    <section>
      <h2>4. Acceptable use</h2>
      <p className="mt-2">You agree not to:</p>
      <ul className="mt-2 space-y-1">
        <li>upload data you do not have permission to use;</li>
        <li>send unsolicited or unlawful communications through the service;</li>
        <li>attempt to bypass plan limits, access other accounts, or disrupt the platform;</li>
        <li>reverse engineer or resell the service without written permission.</li>
      </ul>
    </section>
    <section>
      <h2>5. Availability</h2>
      <p className="mt-2">
        We aim to keep Rumu available and secure but cannot guarantee uninterrupted service. We may
        perform maintenance, update features, or suspend accounts that violate these terms.
      </p>
    </section>
    <section>
      <h2>6. Limitation of liability</h2>
      <p className="mt-2">
        To the maximum extent permitted by law, Rumu is not liable for indirect or consequential loss,
        or for lost profits or data arising from your use of the service. Our aggregate liability is
        limited to the fees you paid in the preceding twelve months.
      </p>
    </section>
    <section>
      <h2>7. Contact</h2>
      <p className="mt-2">
        Questions about these terms can be sent to{' '}
        <a href="mailto:support@rumu.in" className="font-medium text-highlight hover:underline">
          support@rumu.in
        </a>
        .
      </p>
    </section>
  </LegalLayout>
)
