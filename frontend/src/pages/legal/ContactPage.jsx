export const ContactPage = () => (
  <main className="min-h-screen bg-background px-6 py-16 text-foreground">
    <div className="mx-auto max-w-3xl space-y-6">
      <p className="text-sm font-semibold uppercase tracking-wide text-highlight">Rumu support</p>
      <h1 className="text-4xl font-bold">Contact Rumu</h1>
      <p className="text-lg text-muted-foreground">
        For help with your Rumu account, CRM workspace, or AI client connection, email our support team.
      </p>
      <a
        className="inline-flex rounded-lg bg-highlight px-5 py-3 font-semibold text-white hover:opacity-90"
        href="mailto:admin@rumu.in?subject=Rumu%20support"
      >
        Email Rumu support
      </a>
    </div>
  </main>
)
