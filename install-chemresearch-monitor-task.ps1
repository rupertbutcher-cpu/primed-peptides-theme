# Registers a daily Windows Scheduled Task that runs chemresearch_monitor.py -
# checks ChemResearch's live stock and WhatsApps Rupert only if one of the 8
# mapped live Primed Peptides products flips in/out of stock at source.
# Run once, in an admin PowerShell, on the machine that has Chrome + the
# logged-in .chrome-chemresearch-profile (the mini PC).

$ErrorActionPreference = 'Stop'
$pythonExe = 'C:\Users\Rupert\AppData\Local\Programs\Python\Python312\python.exe'
$script    = 'C:\Services\primed_peptides\chemresearch_monitor.py'
$workDir   = 'C:\Services\primed_peptides'
$taskName  = 'HS-ChemResearch-Monitor'

$action  = New-ScheduledTaskAction -Execute $pythonExe -Argument "`"$script`"" -WorkingDirectory $workDir
$trigger = New-ScheduledTaskTrigger -Daily -At 7:00am
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -DontStopOnIdleEnd -ExecutionTimeLimit (New-TimeSpan -Minutes 5)

Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Description "Daily ChemResearch stock check for Primed Peptides - alerts Rupert on WhatsApp only if a live product's stock status changes at source. No WooCommerce changes made." -Force

Write-Output "Registered '$taskName' - runs daily at 7:00am."
Write-Output "IMPORTANT: close any Chrome window open on the .chrome-chemresearch-profile before the scheduled run, or it will fail (profile gets locked to one Chrome process at a time)."
Write-Output "Test it now with: Start-ScheduledTask -TaskName '$taskName'"
