<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authorize — Rumu</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f4f2fa;
            color: #1e1b2e;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(76, 42, 133, 0.12);
            max-width: 420px;
            width: 100%;
            padding: 36px 32px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }
        .brand-badge {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 18px;
        }
        .brand-name { font-size: 20px; font-weight: 700; letter-spacing: -0.02em; }
        h1 { font-size: 18px; font-weight: 600; margin-bottom: 8px; }
        .client {
            background: #f4f2fa;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 14px;
            margin-bottom: 16px;
        }
        .client strong { display: block; font-size: 15px; margin-bottom: 2px; }
        .client span { color: #6b6486; word-break: break-all; }
        .scopes { font-size: 13px; color: #6b6486; margin-bottom: 20px; }
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #3d3757;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #d9d4ec;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 14px;
            transition: border-color 0.15s;
        }
        input:focus { outline: none; border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.12); }
        .error {
            background: #fef1f2;
            border: 1px solid #fbd5d8;
            color: #b3261e;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 14px;
        }
        .actions { display: flex; gap: 12px; margin-top: 8px; }
        button {
            flex: 1;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }
        .approve { background: #7c3aed; color: #fff; }
        .approve:hover { background: #6d28d9; }
        .deny { background: #efedf6; color: #3d3757; }
        .deny:hover { background: #e3dff1; }
        .hint { font-size: 12px; color: #8a84a3; margin-top: 18px; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">
            <div class="brand-badge">R</div>
            <div class="brand-name">Rumu</div>
        </div>

        <h1>Connect your Rumu account</h1>

        <div class="client">
            <strong>{{ $params['client_name'] }}</strong>
            <span>{{ $params['client_id'] }}</span>
        </div>

        <p class="scopes">
            This app will be able to manage your Rumu CRM data (leads, campaigns, tasks, calls, reports)
            as <strong>{{ $params['scopes'] }}</strong> scope while the connection stays active.
        </p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('oauth.authorize.approve') }}">
            @csrf
            @foreach ($params as $key => $value)
                @if ($value !== null)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach

            <label for="email">Rumu account email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">

            <div class="actions">
                <button type="submit" name="decision" value="approve" class="approve">Approve</button>
                <button type="submit" name="decision" value="deny" class="deny">Deny</button>
            </div>
        </form>

        <p class="hint">You can revoke this access anytime by deleting the token from your Rumu profile.</p>
    </div>
</body>
</html>
