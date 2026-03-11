# Security Policy

## Secrets

- Keine Secrets im Repo speichern.
- Keine Tokens/Keys in Beispielen oder Logs.

## Input und Output

- Eingaben validieren und sanitizen.
- Fehlerausgaben ohne sensitive Details.

## Abhaengigkeiten

- Regelmaessige Updates einplanen.
- Kritische CVEs priorisiert beheben.

## Zugriff
<!--
Das Least-Privilege-Prinzip (Prinzip der geringsten Rechte) ist ein Cybersicherheitskonzept, bei dem Benutzern, 
Anwendungen oder Systemen nur die absolut notwendigen Zugriffsrechte gewährt werden, die sie für ihre spezifische
Funktion benötigen. Vereinfacht verkleinert es die Angriffsfläche extrem
-->
- Least-Privilege-Prinzip anwenden. 
- Rechteaenderungen dokumentieren.
