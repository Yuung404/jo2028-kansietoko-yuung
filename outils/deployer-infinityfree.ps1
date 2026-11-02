<#
.SYNOPSIS
    Déploie le site JO 2028 sur InfinityFree par FTP.

.DESCRIPTION
    1. Demande le mot de passe du compte d'hébergement au clavier (saisie masquée,
       jamais affichée ni enregistrée sur le disque de façon durable).
    2. Décompresse l'archive du site dans un dossier temporaire et y génère le fichier
       database/config.php de production (debug désactivé).
    3. Envoie tous les fichiers dans /htdocs, en FTP chiffré (FTPS) si le serveur l'accepte.
    4. Supprime la page par défaut d'InfinityFree (index2.html) puis le dossier temporaire.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File deployer-infinityfree.ps1
    powershell -ExecutionPolicy Bypass -File deployer-infinityfree.ps1 -Simulation
#>
param(
    [string]$Archive          = (Join-Path $PSScriptRoot 'jo2028-site.zip'),
    [string]$FtpHote          = 'ftpupload.net',
    [string]$FtpUtilisateur   = 'if0_43336494',
    [string]$DossierDistant   = '/htdocs',
    [string]$MysqlHote        = 'sql311.infinityfree.com',
    [string]$MysqlBase        = 'if0_43336494_jo2028',
    [string]$MysqlUtilisateur = 'if0_43336494',
    [switch]$Simulation       # prépare tout sans rien envoyer (vérification du script)
)

$ErrorActionPreference = 'Stop'
$temp = Join-Path $env:TEMP 'jo2028-deploiement'

function Ftp-Requete([string]$chemin, [string]$methode) {
    $uri = "ftp://$FtpHote$chemin"
    $r = [System.Net.FtpWebRequest]::Create($uri)
    $r.Method      = $methode
    $r.Credentials = $script:identifiants
    $r.EnableSsl   = $script:chiffre      # FTPS explicite : identifiants et fichiers chiffrés
    $r.UsePassive  = $true
    $r.UseBinary   = $true
    $r.KeepAlive   = $true
    $r.Timeout     = 60000
    return $r
}

function Ftp-CreerDossier([string]$chemin) {
    try { (Ftp-Requete $chemin ([System.Net.WebRequestMethods+Ftp]::MakeDirectory)).GetResponse().Close() }
    catch { }   # le dossier existe déjà : pas une erreur
}

function Ftp-Envoyer([string]$local, [string]$distant) {
    $r = Ftp-Requete $distant ([System.Net.WebRequestMethods+Ftp]::UploadFile)
    $octets = [System.IO.File]::ReadAllBytes($local)
    $r.ContentLength = $octets.Length
    $flux = $r.GetRequestStream(); $flux.Write($octets, 0, $octets.Length); $flux.Close()
    $r.GetResponse().Close()
}

try {
    if (-not (Test-Path $Archive)) { throw "Archive introuvable : $Archive" }

    # --- 1. Mot de passe (saisie masquée)
    if ($Simulation) {
        $motDePasse = 'simulation'
    } else {
        $saisie = Read-Host "Mot de passe du compte d'hébergement $FtpUtilisateur (saisie masquée)" -AsSecureString
        $motDePasse = [System.Net.NetworkCredential]::new('', $saisie).Password
        if ([string]::IsNullOrEmpty($motDePasse)) { throw 'Aucun mot de passe saisi.' }
    }
    $script:identifiants = [System.Net.NetworkCredential]::new($FtpUtilisateur, $motDePasse)

    # --- 2. Préparation locale : archive + config.php de production
    Remove-Item -Recurse -Force $temp -ErrorAction SilentlyContinue
    Expand-Archive -Path $Archive -DestinationPath $temp
    $mdpPhp = $motDePasse.Replace('\', '\\').Replace("'", "\'")     # chaîne PHP entre apostrophes
    $config = @"
<?php
/** Configuration de PRODUCTION (InfinityFree) : générée par deployer-infinityfree.ps1. */
return [
    'host'     => '$MysqlHote',
    'port'     => 3306,
    'dbname'   => '$MysqlBase',
    'user'     => '$MysqlUtilisateur',
    'password' => '$mdpPhp',
    'debug'    => false,
];
"@
    # UTF-8 SANS BOM : un BOM serait envoyé avant les en-têtes HTTP et casserait les sessions
    [System.IO.File]::WriteAllText((Join-Path $temp 'database\config.php'), $config, [System.Text.UTF8Encoding]::new($false))

    $fichiers = Get-ChildItem $temp -Recurse -File
    $dossiers = Get-ChildItem $temp -Recurse -Directory | Sort-Object { $_.FullName.Length }
    Write-Host "Préparé : $($fichiers.Count) fichiers, $($dossiers.Count) dossiers (config.php de production inclus)."

    if ($Simulation) {
        Write-Host '--- SIMULATION : aucun envoi. Fichiers qui seraient envoyés :'
        $fichiers | ForEach-Object { '  ' + $DossierDistant + '/' + $_.FullName.Substring($temp.Length + 1).Replace('\', '/') }
        $ligne = (Get-Content (Join-Path $temp 'database\config.php') -Encoding UTF8 | Select-String 'debug').Line
        Write-Host "config.php (extrait) : $($ligne.Trim())"
        $bom = [System.IO.File]::ReadAllBytes((Join-Path $temp 'database\config.php'))[0..2] -join ','
        Write-Host "3 premiers octets de config.php : $bom (60,63,112 = '<?p' : pas de BOM)"
        return
    }

    # --- 3. Connexion : FTPS d'abord, FTP simple seulement avec accord explicite
    $script:chiffre = $true
    try {
        (Ftp-Requete "$DossierDistant/" ([System.Net.WebRequestMethods+Ftp]::ListDirectory)).GetResponse().Close()
        Write-Host 'Connexion FTPS (chiffrée) établie.' -ForegroundColor Green
    } catch {
        Write-Host "FTPS refusé par le serveur : $($_.Exception.Message)" -ForegroundColor Yellow
        $reponse = Read-Host 'Continuer en FTP NON chiffré (le mot de passe circulera en clair) ? (o/N)'
        if ($reponse -ne 'o') { throw 'Déploiement annulé.' }
        $script:chiffre = $false
        (Ftp-Requete "$DossierDistant/" ([System.Net.WebRequestMethods+Ftp]::ListDirectory)).GetResponse().Close()
    }

    # --- 4. Envoi
    foreach ($d in $dossiers) {
        Ftp-CreerDossier ($DossierDistant + '/' + $d.FullName.Substring($temp.Length + 1).Replace('\', '/'))
    }
    $i = 0
    foreach ($f in $fichiers) {
        $i++
        $distant = $DossierDistant + '/' + $f.FullName.Substring($temp.Length + 1).Replace('\', '/')
        Write-Progress -Activity 'Envoi du site' -Status $distant -PercentComplete (100 * $i / $fichiers.Count)
        Ftp-Envoyer $f.FullName $distant
    }
    Write-Progress -Activity 'Envoi du site' -Completed
    Write-Host "$($fichiers.Count) fichiers envoyés dans $DossierDistant." -ForegroundColor Green

    # --- 5. Page par défaut d'InfinityFree
    try {
        (Ftp-Requete "$DossierDistant/index2.html" ([System.Net.WebRequestMethods+Ftp]::DeleteFile)).GetResponse().Close()
        Write-Host 'Page par défaut index2.html supprimée.'
    } catch { }

    Write-Host 'Déploiement terminé.' -ForegroundColor Green
}
finally {
    # Le dossier temporaire contient config.php (mot de passe) : il est toujours supprimé
    Remove-Item -Recurse -Force $temp -ErrorAction SilentlyContinue
    $motDePasse = $null; $mdpPhp = $null; $config = $null
}
