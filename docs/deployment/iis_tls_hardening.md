# IIS TLS hardening

This runbook is for a Windows Server/IIS administrator. It is intentionally stored with deployment documentation and is blocked from the production web root by `web.config`.

## Scope

Disable TLS 1.0, TLS 1.1 and 3DES at the server level. Retain TLS 1.2 and TLS 1.3 where supported by the installed Windows Server version. Perform the change in an approved maintenance window because it affects every TLS-enabled service on the server.

## Apply

Run PowerShell as Administrator:

```powershell
$protocols = @('TLS 1.0', 'TLS 1.1')
foreach ($protocol in $protocols) {
    foreach ($role in @('Client', 'Server')) {
        $path = "HKLM:\SYSTEM\CurrentControlSet\Control\SecurityProviders\SCHANNEL\Protocols\$protocol\$role"
        New-Item -Path $path -Force | Out-Null
        New-ItemProperty -Path $path -Name Enabled -Value 0 -PropertyType DWord -Force | Out-Null
        New-ItemProperty -Path $path -Name DisabledByDefault -Value 1 -PropertyType DWord -Force | Out-Null
    }
}

$tripleDesPath = 'HKLM:\SYSTEM\CurrentControlSet\Control\SecurityProviders\SCHANNEL\Ciphers\Triple DES 168'
New-Item -Path $tripleDesPath -Force | Out-Null
New-ItemProperty -Path $tripleDesPath -Name Enabled -Value 0 -PropertyType DWord -Force | Out-Null
```

Restart the server after the approved change window. SCHANNEL protocol and cipher changes do not take effect reliably until restart.

## Verify

From an independent scanner or security workstation, confirm:

- TLS 1.0: rejected
- TLS 1.1: rejected
- `TLS_RSA_WITH_3DES_EDE_CBC_SHA`: rejected
- TLS 1.2: accepted
- TLS 1.3: accepted when the operating system supports it

Do not disable TLS 1.2. Manage cipher-suite ordering through the organisation's approved Windows security baseline or Group Policy; prefer AES-GCM and ChaCha20 suites and remove 3DES/64-bit block ciphers.
