import { useState } from 'react'
import { useMutation } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowLeftIcon,
  CheckCircleIcon,
  EnvelopeIcon,
  EyeIcon,
  EyeSlashIcon,
  KeyIcon,
  LockClosedIcon,
} from '@heroicons/react/20/solid'
import { authApi } from '@/api/auth'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Input } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { getErrorMessage } from '@/utils/errors'

export const ForgotPasswordModal = ({ open, onClose, defaultEmail = '', onSuccessEmail }) => {
  const [step, setStep] = useState('email') // 'email' | 'otp' | 'new_password' | 'done'
  const [email, setEmail] = useState(defaultEmail)
  const [otp, setOtp] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [showPassword, setShowPassword] = useState(false)
  const [showConfirm, setShowConfirm] = useState(false)
  const [errorMsg, setErrorMsg] = useState('')

  const handleClose = () => {
    setStep('email')
    setOtp('')
    setPassword('')
    setPasswordConfirmation('')
    setErrorMsg('')
    onClose()
  }

  // Step 1: Send OTP
  const sendOtpMutation = useMutation({
    mutationFn: (targetEmail) => authApi.sendResetOtp({ email: targetEmail.trim() }),
    onSuccess: (response) => {
      const payload = response.data?.data
      toast.success('Verification code sent to your email!')
      if (payload?.debug_otp) {
        // Pre-fill or notify during local testing
        console.log('[Dev] Verification OTP code:', payload.debug_otp)
      }
      setErrorMsg('')
      setStep('otp')
    },
    onError: (err) => {
      setErrorMsg(getErrorMessage(err, 'Could not send verification code.'))
    },
  })

  // Step 2: Verify OTP
  const verifyOtpMutation = useMutation({
    mutationFn: () =>
      authApi.verifyResetOtp({
        email: email.trim(),
        otp: otp.trim(),
      }),
    onSuccess: () => {
      setErrorMsg('')
      setStep('new_password')
    },
    onError: (err) => {
      setErrorMsg(getErrorMessage(err, 'Invalid or expired verification code.'))
    },
  })

  // Step 3: Reset password
  const resetMutation = useMutation({
    mutationFn: () =>
      authApi.resetPasswordWithOtp({
        email: email.trim(),
        otp: otp.trim(),
        password,
        password_confirmation: passwordConfirmation,
      }),
    onSuccess: () => {
      toast.success('Password reset successfully!')
      setErrorMsg('')
      setStep('done')
      onSuccessEmail?.(email.trim())
    },
    onError: (err) => {
      setErrorMsg(getErrorMessage(err, 'Could not reset password.'))
    },
  })

  const submitEmail = (e) => {
    e.preventDefault()
    if (!email.trim()) {
      setErrorMsg('Please enter your email address.')
      return
    }
    setErrorMsg('')
    sendOtpMutation.mutate(email)
  }

  const submitOtp = (e) => {
    e.preventDefault()
    const cleanOtp = otp.trim()
    if (!cleanOtp || cleanOtp.length !== 6) {
      setErrorMsg('Please enter the 6-digit verification code.')
      return
    }
    setErrorMsg('')
    verifyOtpMutation.mutate()
  }

  const submitNewPassword = (e) => {
    e.preventDefault()
    setErrorMsg('')
    if (!password) {
      setErrorMsg('Please enter a new password.')
      return
    }
    if (password.length < 8) {
      setErrorMsg('Password must be at least 8 characters.')
      return
    }
    if (password !== passwordConfirmation) {
      setErrorMsg('Passwords do not match.')
      return
    }
    resetMutation.mutate()
  }

  return (
    <Modal
      open={open}
      onClose={handleClose}
      size="sm"
      title={
        step === 'email'
          ? 'Forgot password'
          : step === 'otp'
            ? 'Enter verification code'
            : step === 'new_password'
              ? 'Set new password'
              : 'Password updated'
      }
      description={
        step === 'email'
          ? "Enter your account email and we'll send a 6-digit verification code."
          : step === 'otp'
            ? `We sent a 6-digit code to ${email}.`
            : step === 'new_password'
              ? 'Create a new secure password for your account.'
              : 'Your password has been changed successfully.'
      }
    >
      <div className="p-5">
        {errorMsg && (
          <Alert variant="danger" className="mb-4" onDismiss={() => setErrorMsg('')}>
            {errorMsg}
          </Alert>
        )}

        {step === 'email' && (
          <form onSubmit={submitEmail} className="space-y-4">
            <FormField label="Email address" htmlFor="reset_email" required>
              <Input
                id="reset_email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="you@company.com"
                leftIcon={EnvelopeIcon}
                autoFocus
              />
            </FormField>

            <div className="flex items-center justify-end gap-2 pt-2">
              <Button type="button" variant="outline" onClick={handleClose}>
                Cancel
              </Button>
              <Button type="submit" loading={sendOtpMutation.isPending}>
                Send code
              </Button>
            </div>
          </form>
        )}

        {step === 'otp' && (
          <form onSubmit={submitOtp} className="space-y-4">
            <FormField
              label="6-digit verification code"
              htmlFor="otp_code"
              hint="Check your inbox (and spam folder) for the verification code."
              required
            >
              <Input
                id="otp_code"
                type="text"
                maxLength={6}
                value={otp}
                onChange={(e) => setOtp(e.target.value.replace(/\D/g, ''))}
                placeholder="123456"
                leftIcon={KeyIcon}
                className="text-center font-mono text-xl tracking-widest"
                autoFocus
              />
            </FormField>

            <div className="flex items-center justify-between pt-1">
              <button
                type="button"
                onClick={() => {
                  setErrorMsg('')
                  setStep('email')
                }}
                className="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
              >
                <ArrowLeftIcon className="h-3.5 w-3.5" />
                Change email
              </button>

              <button
                type="button"
                disabled={sendOtpMutation.isPending}
                onClick={() => sendOtpMutation.mutate(email)}
                className="text-xs font-semibold text-highlight hover:underline disabled:opacity-50"
              >
                {sendOtpMutation.isPending ? 'Resending…' : 'Resend code'}
              </button>
            </div>

            <div className="flex items-center justify-end gap-2 pt-3 border-t border-border">
              <Button type="button" variant="outline" onClick={handleClose}>
                Cancel
              </Button>
              <Button type="submit" loading={verifyOtpMutation.isPending}>
                Verify code
              </Button>
            </div>
          </form>
        )}

        {step === 'new_password' && (
          <form onSubmit={submitNewPassword} className="space-y-4">
            <FormField label="New password" htmlFor="new_password" required hint="At least 8 characters">
              <Input
                id="new_password"
                type={showPassword ? 'text' : 'password'}
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Enter new password"
                leftIcon={LockClosedIcon}
                rightSlot={
                  <button
                    type="button"
                    onClick={() => setShowPassword((v) => !v)}
                    className="rounded p-1 text-muted-foreground hover:text-foreground"
                  >
                    {showPassword ? <EyeSlashIcon className="h-4 w-4" /> : <EyeIcon className="h-4 w-4" />}
                  </button>
                }
                autoFocus
              />
            </FormField>

            <FormField label="Confirm new password" htmlFor="confirm_password" required>
              <Input
                id="confirm_password"
                type={showConfirm ? 'text' : 'password'}
                value={passwordConfirmation}
                onChange={(e) => setPasswordConfirmation(e.target.value)}
                placeholder="Confirm new password"
                leftIcon={LockClosedIcon}
                rightSlot={
                  <button
                    type="button"
                    onClick={() => setShowConfirm((v) => !v)}
                    className="rounded p-1 text-muted-foreground hover:text-foreground"
                  >
                    {showConfirm ? <EyeSlashIcon className="h-4 w-4" /> : <EyeIcon className="h-4 w-4" />}
                  </button>
                }
              />
            </FormField>

            <div className="flex items-center justify-end gap-2 pt-3 border-t border-border">
              <Button type="button" variant="outline" onClick={handleClose}>
                Cancel
              </Button>
              <Button type="submit" loading={resetMutation.isPending}>
                Reset password
              </Button>
            </div>
          </form>
        )}

        {step === 'done' && (
          <div className="py-3 text-center">
            <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
              <CheckCircleIcon className="h-8 w-8" />
            </div>
            <h3 className="text-base font-semibold text-foreground">Password updated successfully</h3>
            <p className="mt-1 text-sm text-muted-foreground">
              You can now sign in using your new password.
            </p>
            <div className="mt-6">
              <Button type="button" className="w-full" onClick={handleClose}>
                Back to sign in
              </Button>
            </div>
          </div>
        )}
      </div>
    </Modal>
  )
}
