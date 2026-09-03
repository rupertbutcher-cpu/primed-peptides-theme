# Registers a daily Windows Scheduled Task that runs chemresearch_monitor.py -
# checks ChemResearch's live stock and WhatsApps Rupert only if one of the 8
# mapped live Primed Peptides products flips in/out of stock at source.
# Run once, in an admin PowerShell, on the machine that has Chrome + the
# logged-in .chrome-chemresearch-profile (the mini PC).
#
# Uses pythonw.exe (not python.exe) + -Hidden so this runs as a true background job -
# 2026-09-03 (Rupert): python.exe under Task Scheduler flashed a visible console window
# on the server screen at 7am. Same fix already applied to HS-Peptide-Email-Monitor on
# 2026-08-21. No script output is lost: chemresearch_monitor.py writes its own
# chemresearch_monitor.log and Task Scheduler discarded stdout/stderr either way.
# (The Chrome window the script spawns is already pushed off-screen + minimized in-script.)

$ErrorActionPreference = 'Stop'
$pythonExe = 'C:\Users\Rupert\AppData\Local\Programs\Python\Python312\pythonw.exe'
$script    = 'C:\Services\primed_peptides\chemresearch_monitor.py'
$workDir   = 'C:\Services\primed_peptides'
$taskName  = 'HS-ChemResearch-Monitor'

$action  = New-ScheduledTaskAction -Execute $pythonExe -Argument "`"$script`"" -WorkingDirectory $workDir
$trigger = New-ScheduledTaskTrigger -Daily -At 7:00am
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -DontStopOnIdleEnd -ExecutionTimeLimit (New-TimeSpan -Minutes 5) -Hidden

Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Description "Daily ChemResearch stock check for Primed Peptides - alerts Rupert on WhatsApp only if a live product's stock status changes at source. No WooCommerce changes made." -Force

Write-Output "Registered '$taskName' - runs daily at 7:00am."
Write-Output "IMPORTANT: close any Chrome window open on the .chrome-chemresearch-profile before the scheduled run, or it will fail (profile gets locked to one Chrome process at a time)."
Write-Output "Test it now with: Start-ScheduledTask -TaskName '$taskName'"
