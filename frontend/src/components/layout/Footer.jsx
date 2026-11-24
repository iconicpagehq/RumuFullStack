import { Link } from 'react-router-dom'

export const Footer = ({ className = '' }) => {
  const currentYear = new Date().getFullYear()

  return (
    <footer className={`border-t border-border py-6 text-xs text-muted-foreground ${className}`}>
      <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
        <p>© {currentYear} Rumu. All rights reserved.</p>
        <div className="flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
          <Link to="/privacy-policy" className="hover:text-foreground transition">
            Privacy Policy
          </Link>
          <Link to="/terms-of-service" className="hover:text-foreground transition">
            Terms of Service
          </Link>
          <Link to="/data-deletion" className="hover:text-foreground transition">
            User Data Deletion
          </Link>
          <Link to="/contact" className="hover:text-foreground transition">
            Contact Us
          </Link>
        </div>
      </div>
    </footer>
  )
}
