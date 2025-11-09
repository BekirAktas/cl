import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';

export default function Tournament({ teams = [], league = {} }) {
    const [loading, setLoading] = useState(false);

    const generateFixtures = async () => {
        setLoading(true);
        try {
            const response = await axios.post('/api/generate-fixtures');
            if (response.data.success) {
                router.visit('/fixtures');
            } else {
                alert('Error: ' + response.data.message);
            }
        } catch (error) {
            console.error(error);
            alert('Could not create fixtures!');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 p-8">
            <Head title="Tournament" />
            <div className="max-w-5xl mx-auto space-y-8">
                <div className="bg-white rounded-2xl shadow-xl overflow-hidden">
                    <div className="bg-gradient-to-r from-slate-700 to-slate-900 p-8">
                        <h1 className="text-3xl font-bold text-white">Tournament Teams</h1>
                        <p className="text-slate-300 mt-2">
                            Ready to generate fixtures and start the simulation
                        </p>
                    </div>
                    <div className="p-8">
                        <div className="mb-8">
                            <h2 className="text-xl font-bold text-slate-800 mb-4">Teams</h2>
                            <div className="space-y-2">
                                {teams.map((team) => (
                                    <div
                                        key={team.id}
                                        className="flex items-center justify-between p-4 bg-slate-50 rounded-lg"
                                    >
                                        <span className="text-lg text-slate-800">{team.name}</span>
                                    </div>
                                ))}
                                {teams.length === 0 && (
                                    <p className="text-slate-500">No teams found. Run the seeder to populate Redis.</p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-4">
                            <button
                                onClick={generateFixtures}
                                disabled={loading || teams.length < 2}
                                className="w-full py-4 bg-cyan-500 text-white text-lg font-semibold rounded-lg hover:bg-cyan-600 transition-colors shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {loading ? 'Creating Fixtures...' : 'Generate Fixtures'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
