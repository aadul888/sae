    <!-- User Profile Badge -->
    <div class="dash-sidebar-user">
        <a href="{{ route('dashboard.profile') }}" title="Lihat Profil Saya"
            style="text-decoration: none; flex-shrink: 0; display: block;">
            @if ($userFoto)
                <img src="{{ $userFoto }}" alt="{{ $userName }}" class="dash-sidebar-avatar-img"
                    onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                <div class="dash-sidebar-user-avatar" style="display: none;">
                    @if ($role === 'admin')
                        <i class="fas fa-user-shield"></i>
                    @elseif($role === 'guru')
                        <i class="fas fa-chalkboard-user"></i>
                    @elseif($role === 'tendik')
                        <i class="fas fa-id-badge"></i>
                    @else
                        <i class="fas fa-user-graduate"></i>
                    @endif
                </div>
            @else
                <div class="dash-sidebar-user-avatar">
                    @if ($role === 'admin')
                        <i class="fas fa-user-shield"></i>
                    @elseif($role === 'guru')
                        <i class="fas fa-chalkboard-user"></i>
                    @elseif($role === 'tendik')
                        <i class="fas fa-id-badge"></i>
                    @else
                        <i class="fas fa-user-graduate"></i>
                    @endif
                </div>
            @endif
        </a>
        <div class="dash-user-info">
            <a href="{{ route('dashboard.profile') }}" class="dash-user-name" title="{{ $userName }}"
                style="text-decoration: none; color: inherit; display: block;">{{ $userName }}</a>
            <div style="display: flex; align-items: center; gap: 4px; flex-wrap: wrap; margin-top: 2px;">
                <span class="dash-user-role role-{{ $role }}">{{ $role }}</span>
                @foreach ($userDuties as $duty)
                    <span class="badge badge-accent"
                        style="font-size: 0.62rem; padding: 1px 5px; border-radius: 4px; text-transform: none; max-width: 130px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                        title="{{ $duty->nama }}{{ $duty->ekuivalensi_jam ? ' (' . $duty->ekuivalensi_jam . ' Jam)' : '' }}">
                        {{ $duty->nama }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>
