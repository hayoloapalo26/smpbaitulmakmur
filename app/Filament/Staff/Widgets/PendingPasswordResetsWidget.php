<?php

namespace App\Filament\Staff\Widgets;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class PendingPasswordResetsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    
    // Only visible for superadmin and tata_usaha
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(['superadmin', 'tata_usaha']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PasswordResetRequest::query()
                    ->where('status', 'pending')
                    ->latest()
            )
            ->heading('Permintaan Reset Password (Manual)')
            ->description('Daftar pengguna yang meminta reset password.')
            ->columns([
                Tables\Columns\TextColumn::make('user_input')
                    ->label('Username / Email')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Request')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat_user')
                    ->label('Lihat User')
                    ->icon('heroicon-o-user')
                    ->color('info')
                    ->url(function (PasswordResetRequest $record) {
                        // Cari user berdasarkan email atau username
                        $user = User::where('email', $record->user_input)
                                    ->orWhere('username', $record->user_input)
                                    ->first();
                        
                        if ($user && class_exists(\App\Filament\Staff\Resources\UserResource::class)) {
                            return \App\Filament\Staff\Resources\UserResource::getUrl('edit', ['record' => $user->id]);
                        }
                        return null;
                    })
                    ->openUrlInNewTab()
                    ->hidden(function (PasswordResetRequest $record) {
                        return !User::where('email', $record->user_input)
                                    ->orWhere('username', $record->user_input)
                                    ->exists();
                    }),
                
                Tables\Actions\Action::make('tandai_selesai')
                    ->label('Tandai Selesai')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Tandai Permintaan Selesai')
                    ->modalDescription('Pastikan Anda sudah me-reset password pengguna ini sebelum menandai selesai.')
                    ->action(function (PasswordResetRequest $record) {
                        $record->update(['status' => 'resolved']);
                        Notification::make()
                            ->title('Permintaan Diselesaikan')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Tidak ada permintaan reset password.');
    }
}
