<?php

namespace App\Filament\Resources\Tenant;

use App\Filament\Resources\Tenant\GuestResource\Pages;
use App\Filament\Traits\HasPermissionBasedAccess;
use App\Models\Guest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GuestResource extends Resource
{
    use HasPermissionBasedAccess;
    
    public static function shouldRegisterNavigation(): bool
    {
        return Auth::guard('tenant')->check() && 
               Auth::guard('tenant')->user()->can('view guest');
    }
    protected static ?string $model = Guest::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Property Management';
    protected static ?string $pluralModelLabel = 'Profiles';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
           	 ->schema([
                Forms\Components\Section::make('Guest Information')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\Select::make('type')
                            ->options([
                                'RESIDENT' => 'Resident',
                                'STAFF' => 'Staff',
                                'CONTRACTOR' => 'Contractor',
                                'VISITORS' => 'Visitor',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('is_active')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
                
                // Type-specific fields section - Renamed from "Population Management" to "Guest Requests"
                Forms\Components\Section::make('Guest Requests')
                    ->schema([
                        // TRN - Only required for Residents
                        Forms\Components\TextInput::make('trn')
                            ->label('TRN')
                            ->helperText('Required for Residents only')
                            ->required(fn (callable $get) => $get('type') === 'RESIDENT')
                            ->visible(fn (callable $get) => $get('type') === 'RESIDENT')
                            ->maxLength(255),
                            
                        // Room - Only for Residents
                        Forms\Components\Select::make('assigned_room_id')
                            ->label('Assigned Room')
                            ->relationship('assignedRoom', 'room_no', function ($query) {
                                return $query->orderBy('room_no');
                            })
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->room_no} ({$record->building}, Floor {$record->floor})")
                            ->searchable(['room_no', 'building', 'floor'])
                            ->preload()
                            ->required(fn (callable $get) => $get('type') === 'RESIDENT')
                            ->visible(fn (callable $get) => $get('type') === 'RESIDENT')
                            ->helperText('Room assignment for residents. The resident will be automatically checked in to this room.'),
                            
                        // PPS Number - Only for Staff
                        Forms\Components\TextInput::make('pps_number')
                            ->label('PPS Number')
                            ->helperText('Required for Staff only')
                            ->required(fn (callable $get) => $get('type') === 'STAFF')
                            ->visible(fn (callable $get) => $get('type') === 'STAFF')
                            ->maxLength(255),
                            
                        // IBAN - Only for Staff
                        Forms\Components\TextInput::make('iban')
                            ->label('IBAN')
                            ->helperText('Required for Staff only')
                            ->required(fn (callable $get) => $get('type') === 'STAFF')
                            ->visible(fn (callable $get) => $get('type') === 'STAFF')
                            ->maxLength(255),
                            
                        // Job Title - Only for Staff
                        Forms\Components\TextInput::make('job_title')
                            ->label('Job Title')
                            ->helperText('Required for Staff only')
                            ->required(fn (callable $get) => $get('type') === 'STAFF')
                            ->visible(fn (callable $get) => $get('type') === 'STAFF')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\TextInput::make('middle_name')
                            ->maxLength(255),
                        Forms\Components\Select::make('sex')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                                'other' => 'Other',
                            ]),
                        Forms\Components\DatePicker::make('date_of_birth'),
                        Forms\Components\Select::make('nationality')
							->options([
								'Afghanistan' => 'Afghanistan',
								'Albania' => 'Albania',
								'Algeria' => 'Algeria',
								'Andorra' => 'Andorra',
								'Angola' => 'Angola',
								'Antigua and Barbuda' => 'Antigua and Barbuda',
								'Argentina' => 'Argentina',
								'Armenia' => 'Armenia',
								'Austria' => 'Austria',
								'Azerbaijan' => 'Azerbaijan',
								'Bahrain' => 'Bahrain',
								'Bangladesh' => 'Bangladesh',
								'Barbados' => 'Barbados',
								'Belarus' => 'Belarus',
								'Belgium' => 'Belgium',
								'Belize' => 'Belize',
								'Benin' => 'Benin',
								'Bhutan' => 'Bhutan',
								'Bolivia' => 'Bolivia',
								'Bosnia and Herzegovina' => 'Bosnia and Herzegovina',
								'Botswana' => 'Botswana',
								'Brazil' => 'Brazil',
								'Brunei' => 'Brunei',
								'Bulgaria' => 'Bulgaria',
								'Burkina Faso' => 'Burkina Faso',
								'Burundi' => 'Burundi',
								'Cabo Verde' => 'Cabo Verde',
								'Cambodia' => 'Cambodia',
								'Cameroon' => 'Cameroon',
								'Canada' => 'Canada',
								'Central African Republic' => 'Central African Republic',
								'Chad' => 'Chad',
								'Channel Islands' => 'Channel Islands',
								'Chile' => 'Chile',
								'China' => 'China',
								'Colombia' => 'Colombia',
								'Comoros' => 'Comoros',
								'Congo' => 'Congo',
								'Costa Rica' => 'Costa Rica',
								'Côte d\'Ivoire' => 'Côte d\'Ivoire',
								'Croatia' => 'Croatia',
								'Cuba' => 'Cuba',
								'Cyprus' => 'Cyprus',
								'Czech Republic' => 'Czech Republic',
								'Denmark' => 'Denmark',
								'Djibouti' => 'Djibouti',
								'Dominica' => 'Dominica',
								'Dominican Republic' => 'Dominican Republic',
								'DR Congo' => 'DR Congo',
								'Ecuador' => 'Ecuador',
								'Egypt' => 'Egypt',
								'El Salvador' => 'El Salvador',
								'Equatorial Guinea' => 'Equatorial Guinea',
								'Eritrea' => 'Eritrea',
								'Estonia' => 'Estonia',
								'Eswatini' => 'Eswatini',
								'Ethiopia' => 'Ethiopia',
								'Faeroe Islands' => 'Faeroe Islands',
								'Finland' => 'Finland',
								'France' => 'France',
								'French Guiana' => 'French Guiana',
								'Gabon' => 'Gabon',
								'Gambia' => 'Gambia',
								'Georgia' => 'Georgia',
								'Germany' => 'Germany',
								'Ghana' => 'Ghana',
								'Gibraltar' => 'Gibraltar',
								'Greece' => 'Greece',
								'Grenada' => 'Grenada',
								'Guatemala' => 'Guatemala',
								'Guinea' => 'Guinea',
								'Guinea-Bissau' => 'Guinea-Bissau',
								'Guyana' => 'Guyana',
								'Haiti' => 'Haiti',
								'Holy See' => 'Holy See',
								'Honduras' => 'Honduras',
								'Hong Kong' => 'Hong Kong',
								'Hungary' => 'Hungary',
								'Iceland' => 'Iceland',
								'India' => 'India',
								'Indonesia' => 'Indonesia',
								'Iran' => 'Iran',
								'Iraq' => 'Iraq',
								'Ireland' => 'Ireland',
								'Isle of Man' => 'Isle of Man',
								'Israel' => 'Israel',
								'Italy' => 'Italy',
								'Jamaica' => 'Jamaica',
								'Japan' => 'Japan',
								'Jordan' => 'Jordan',
								'Kazakhstan' => 'Kazakhstan',
								'Kenya' => 'Kenya',
								'Kuwait' => 'Kuwait',
								'Kyrgyzstan' => 'Kyrgyzstan',
								'Laos' => 'Laos',
								'Latvia' => 'Latvia',
								'Lebanon' => 'Lebanon',
								'Lesotho' => 'Lesotho',
								'Liberia' => 'Liberia',
								'Libya' => 'Libya',
								'Liechtenstein' => 'Liechtenstein',
								'Lithuania' => 'Lithuania',
								'Luxembourg' => 'Luxembourg',
								'Macao' => 'Macao',
								'Madagascar' => 'Madagascar',
								'Malawi' => 'Malawi',
								'Malaysia' => 'Malaysia',
								'Maldives' => 'Maldives',
								'Mali' => 'Mali',
								'Malta' => 'Malta',
								'Mauritania' => 'Mauritania',
								'Mauritius' => 'Mauritius',
								'Mayotte' => 'Mayotte',
								'Mexico' => 'Mexico',
								'Moldova' => 'Moldova',
								'Monaco' => 'Monaco',
								'Mongolia' => 'Mongolia',
								'Montenegro' => 'Montenegro',
								'Morocco' => 'Morocco',
								'Mozambique' => 'Mozambique',
								'Myanmar' => 'Myanmar',
								'Namibia' => 'Namibia',
								'Nepal' => 'Nepal',
								'Netherlands' => 'Netherlands',
								'Nicaragua' => 'Nicaragua',
								'Niger' => 'Niger',
								'Nigeria' => 'Nigeria',
								'North Korea' => 'North Korea',
								'North Macedonia' => 'North Macedonia',
								'Norway' => 'Norway',
								'Oman' => 'Oman',
								'Pakistan' => 'Pakistan',
								'Panama' => 'Panama',
								'Paraguay' => 'Paraguay',
								'Peru' => 'Peru',
								'Philippines' => 'Philippines',
								'Poland' => 'Poland',
								'Portugal' => 'Portugal',
								'Qatar' => 'Qatar',
								'Réunion' => 'Réunion',
								'Romania' => 'Romania',
								'Russia' => 'Russia',
								'Rwanda' => 'Rwanda',
								'Saint Helena' => 'Saint Helena',
								'Saint Kitts and Nevis' => 'Saint Kitts and Nevis',
								'Saint Lucia' => 'Saint Lucia',
								'Saint Vincent and the Grenadines' => 'Saint Vincent and the Grenadines',
								'San Marino' => 'San Marino',
								'Sao Tome & Principe' => 'Sao Tome & Principe',
								'Saudi Arabia' => 'Saudi Arabia',
								'Senegal' => 'Senegal',
								'Serbia' => 'Serbia',
								'Seychelles' => 'Seychelles',
								'Sierra Leone' => 'Sierra Leone',
								'Singapore' => 'Singapore',
								'Slovakia' => 'Slovakia',
								'Slovenia' => 'Slovenia',
								'Somalia' => 'Somalia',
								'South Africa' => 'South Africa',
								'South Korea' => 'South Korea',
								'South Sudan' => 'South Sudan',
								'Spain' => 'Spain',
								'Sri Lanka' => 'Sri Lanka',
								'State of Palestine' => 'State of Palestine',
								'Stateless' => 'Stateless',
								'Sudan' => 'Sudan',
								'Suriname' => 'Suriname',
								'Sweden' => 'Sweden',
								'Switzerland' => 'Switzerland',
								'Syria' => 'Syria',
								'Taiwan' => 'Taiwan',
								'Tajikistan' => 'Tajikistan',
								'Tanzania' => 'Tanzania',
								'Thailand' => 'Thailand',
								'The Bahamas' => 'The Bahamas',
								'Timor-Leste' => 'Timor-Leste',
								'Togo' => 'Togo',
								'Trinidad and Tobago' => 'Trinidad and Tobago',
								'Tunisia' => 'Tunisia',
								'Turkey' => 'Turkey',
								'Turkmenistan' => 'Turkmenistan',
								'Uganda' => 'Uganda',
								'Ukraine' => 'Ukraine',
								'United Arab Emirates' => 'United Arab Emirates',
								'United Kingdom' => 'United Kingdom',
								'United States' => 'United States',
								'Uruguay' => 'Uruguay',
								'Uzbekistan' => 'Uzbekistan',
								'Venezuela' => 'Venezuela',
								'Vietnam' => 'Vietnam',
								'Western Sahara' => 'Western Sahara',
								'Yemen' => 'Yemen',
								'Zambia' => 'Zambia',
								'Zimbabwe' => 'Zimbabwe',
							]),
                        Forms\Components\Select::make('marital_status')
                            ->options([
                                'SINGLE' => 'Single',
                                'MARRIED' => 'Married',
                                'DIVORCED' => 'Divorced',
                                'WIDOWED' => 'Widowed',
                            ]),
                        Forms\Components\FileUpload::make('photo')
                            ->directory('guests/photos')
                            ->image()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('300')
                            ->imageResizeTargetHeight('300'),
                        Forms\Components\Textarea::make('medical_history')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'RESIDENT' => 'success',
                        'STAFF' => 'info',
                        'CONTRACTOR' => 'warning',
                        'VISITORS' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->getStateUsing(fn (Guest $record): bool => $record->is_active === 'active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'RESIDENT' => 'Resident',
                        'STAFF' => 'Staff',
                        'CONTRACTOR' => 'Contractor',
                        'VISITORS' => 'Visitor',
                    ]),
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('view guest')),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('update guest')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('delete guest')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('delete guest')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuests::route('/'),
            'create' => Pages\CreateGuest::route('/create'),
            'view' => Pages\ViewGuest::route('/{record}'),
            'edit' => Pages\EditGuest::route('/{record}/edit'),
        ];
    }
}
