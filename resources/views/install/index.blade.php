<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installer SAE - Smart Apps Education</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/sae.css') }}">
</head>

<body>
    <div class="ambient-glow"></div>
    <div class="ambient-glow-2"></div>

    <div class="installer-wrapper">
        <div class="installer-card">
            <div class="text-center mb-4">
                <div
                    style="width: 60px; height: 60px; background: rgba(59, 130, 246, 0.15); border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                    <i class="fas fa-database text-primary" style="font-size: 1.75rem;"></i>
                </div>
                <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.25rem;">Installer Sistem SAE</h2>
                <p class="text-muted" style="font-size: 0.85rem;">Hubungkan database server untuk instalasi baru</p>
            </div>

            @if (session('error'))
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form action="{{ route('install.process') }}" method="POST">
                @csrf
                <input type="hidden" name="db_host" value="{{ old('db_host', '127.0.0.1') }}">
                <input type="hidden" name="db_port" value="{{ old('db_port', '3306') }}">

                <div class="form-group">
                    <label class="form-label"><i class="fas fa-database me-1"></i> Nama Database</label>
                    <input type="text" name="db_name" class="input-control" value="{{ old('db_name', 'db_sae') }}"
                        placeholder="contoh: db_sae" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fas fa-user me-1"></i> Username Database</label>
                    <input type="text" name="db_user" class="input-control" value="{{ old('db_user', 'root') }}"
                        placeholder="contoh: root" required>
                </div>

                <div class="form-group mb-4">
                    <label class="form-label"><i class="fas fa-key me-1"></i> Password Database</label>
                    <input type="password" name="db_pass" class="input-control"
                        placeholder="Kosongkan jika tanpa password">
                </div>

                <div
                    style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px; padding: 12px; margin-bottom: 1.5rem; font-size: 0.78rem; color: var(--text-muted);">
                    <div style="font-weight: 700; color: var(--text-main); margin-bottom: 4px;"><i
                            class="fas fa-info-circle text-primary me-1"></i> Akun Bawaan Awal:</div>
                    <div>• <b>Admin:</b> admin@sae.id / Admin543!</div>
                    <div>• <b>Guru:</b> gtk@sae.id / Geteka543!</div>
                    <div>• <b>Siswa:</b> siswa@sae.id / Siswa543!</div>
                </div>

                <button type="submit" class="btn btn-primary"
                    style="width: 100%; padding: 0.85rem; font-size: 0.95rem;">
                    <i class="fas fa-rocket me-2"></i> Mulai Instalasi & Migrasi
                </button>
            </form>
        </div>
    </div>
</body>

</html>
