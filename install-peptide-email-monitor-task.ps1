# Registers a Windows Scheduled Task that runs peptide_email_monitor.py every 15
# minutes - checks both Primed Peptides and Premium Peptide inboxes for new mail
# and WhatsApps Rupert a From/Subject summary if anything new has arrived.
# Read-only (BODY.PEEK, IMAP opened readonly) - never marks mail as read or moves it.
# Run once, in an admin PowerShell, on the machine that can reach hs-bot on localhost:3001.

$ErrorActionPreference = 'Stop'
$pythonExe = 'C:\Users\Rupert\AppData\Local\Programs\Python\Python312\python.exe'
$script    = 'C:\Services\primed_peptides\peptide_email_monitor.py'
$workDir   = 'C:\Services\primed_peptides'
$taskName  = 'HS-Peptide-Email-Monitor'

$action   = New-ScheduledTaskAction -Execute $pythonExe -Argument "`"$script`"" -WorkingDirectory $workDir
# [TimeSpan]::MaxValue (~29,000 years) produces a duration string Task Scheduler's XML
# schema rejects outright (real error hit 2026-08-19: "value ... incorrectly formatted or
# out of range"). 10 years is comfortably "forever" for this purpose and is a valid duration.
$trigger  = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 15) -RepetitionDuration (New-TimeSpan -Days 3650)
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -DontStopOnIdleEnd -ExecutionTimeLimit (New-TimeSpan -Minutes 3)

try {
    Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Description "Checks both peptide-brand inboxes every 15 min, WhatsApps Rupert a summary of any new mail. Read-only, no mail is marked read/moved." -Force -ErrorAction Stop | Out-Null
    Write-Output "Registered '$taskName' - runs every 15 minutes."
    Write-Output "Test it now with: Start-ScheduledTask -TaskName '$taskName'"
} catch {
    Write-Output "FAILED to register the task: $($_.Exception.Message)"
    throw
}
