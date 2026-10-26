<?php
$file = 'app/Filament/Staff/Resources/UserResource.php';
$content = file_get_contents($file);

$actionCode = <<<'PHP'
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('reset_password')
                    ->label('Ubah Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->visible(fn () => auth()->user()->hasRole('superadmin'))
                    ->form([
                        \Filament\Forms\Components\TextInput::make('new_password')
                            ->label('Password Baru')
                            ->password()
                            ->required()
                            ->minLength(6),
                        \Filament\Forms\Components\TextInput::make('new_password_confirmation')
                            ->label('Konfirmasi Password Baru')
                            ->password()
                            ->required()
                            ->same('new_password'),
                    ])
                    ->action(function (App\Models\User $record, array $data) {
                        $record->update([
                            'password' => \Illuminate\Support\Facades\Hash::make($data['new_password'])
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Password berhasil diubah')
                            ->success()
                            ->send();
                    }),
PHP;

$content = str_replace('Tables\Actions\EditAction::make(),', $actionCode, $content);
file_put_contents($file, $content);
echo "UserResource updated with reset password action.\n";
