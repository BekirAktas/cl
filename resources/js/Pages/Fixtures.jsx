import { Head, Link } from '@inertiajs/react';

export default function Fixtures({ fixtures = [] }) {
    const weeks = fixtures.map((_, index) => index + 1);

    return (
        <div className="min-h-screen bg-slate-50 p-8">
            <Head title="Fixtures" />

            <div className="max-w-6xl mx-auto">
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-bold text-slate-900">League Fixtures</h1>
                        <p className="text-slate-500">All scheduled matches grouped by week.</p>
                    </div>
                    <Link
                        href={route('home')}
                        className="px-5 py-3 bg-slate-800 text-white rounded-lg shadow hover:bg-slate-900"
                    >
                        Back to Overview
                    </Link>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    {weeks.length > 0 ? weeks.map((week) => (
                        <div key={week} className="bg-white rounded-2xl shadow p-6">
                            <h2 className="text-xl font-semibold text-slate-800 mb-4">Week {week}</h2>
                            <div className="space-y-3">
                                {fixtures[week - 1]?.map((match, matchIndex) => (
                                    <div
                                        key={matchIndex}
                                        className="flex items-center justify-between p-4 bg-slate-50 rounded-xl"
                                    >
                                        <span className="font-medium text-slate-700">{match.home.name}</span>
                                        <span className="text-sm text-slate-400">vs</span>
                                        <span className="font-medium text-slate-700">{match.away.name}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )) : (
                        <div className="col-span-2 text-center py-8">
                            <p className="text-slate-500">No fixtures created yet.</p>
                        </div>
                    )}
                </div>

                <div className="mt-10 flex justify-center">
                    <Link
                        href={route('simulation')}
                        className="px-6 py-4 bg-cyan-500 text-white font-semibold rounded-xl shadow hover:bg-cyan-600"
                    >
                        Start Simulation
                    </Link>
                </div>
            </div>
        </div>
    );
}
