import { useState } from "react";
import { format } from "date-fns";
import { Link, useForm } from "@inertiajs/react";

export default function WorkExperienceInput({ user, setMessage }) {
    const { data, setData, patch, processing, errors } = useForm({
        company: null,
        position: null,
        start_date: null,
        end_date: null,
        is_current: false,
        description: null,
    });

    const [isUpdating, setIsUpdating] = useState(false);
    const [messageWorkExp, setMessageWorkExp] = useState(null);

    const handleCurrentJobChange = (e) => {
        const isChecked = e.target.checked;
        setData("is_current", isChecked);
        setData("end_date", isChecked ? "" : data.end_date);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        patch(`/user/${user.username}/work-experiences`, {
            preserveScroll: true,
            onSuccess: () => {
                setMessageWorkExp("Work experience updated successfully.");
                setData({
                    company: "",
                    position: "",
                    start_date: "",
                    end_date: "",
                    is_current: false,
                    description: "",
                });
                setIsUpdating(false);
            },
        });
    };

    return (
        <div className="mt-6">
            <h2 className="text-xl font-semibold mb-4">Work Experience</h2>
            {messageWorkExp && (
                <div
                    role="alert"
                    className="alert alert-success alert-soft mb-4 flex items-center justify-between"
                >
                    <span>{messageWorkExp}</span>
                    <button
                        className="btn btn-sm btn-ghost"
                        onClick={() => setMessageWorkExp(null)}
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            className="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            )}

            <div className="grid grid-cols-1">
                {/* Existing Experiences */}
                {user.work_experiences &&
                    user.work_experiences?.map((exp) => (
                        <div
                            key={exp.id}
                            className="mb-4 p-4 border rounded-lg w-full"
                        >
                            <div className="flex justify-between">
                                <div>
                                    <p className="font-medium">
                                        {exp.position} at {exp.company}
                                    </p>
                                    <p className="text-sm text-gray-600">
                                        {format(
                                            new Date(exp.start_date),
                                            "MMM yyyy"
                                        )}{" "}
                                        -{" "}
                                        {exp.is_current
                                            ? "Present"
                                            : format(
                                                  new Date(exp.end_date),
                                                  "MMM yyyy"
                                              )}
                                    </p>
                                </div>
                                {/* Update Button */}
                                <button
                                    onClick={() => {
                                        //populate the form with existing data for editing
                                        setData({
                                            id: exp.id,
                                            company: exp.company,
                                            position: exp.position,
                                            start_date: exp.start_date?.slice(
                                                0,
                                                10
                                            ),
                                            end_date: exp.end_date?.slice(
                                                0,
                                                10
                                            ),
                                            is_current: exp.is_current,
                                            description: exp.description,
                                        });
                                        setIsUpdating(true);

                                        //scroll to the form
                                        const formElement =
                                            document.querySelector(
                                                "#work-experience-form"
                                            );
                                        if (formElement) {
                                            formElement.scrollIntoView({
                                                behavior: "smooth",
                                                block: "start",
                                            });
                                        }
                                    }}
                                    className="text-blue-500 hover:text-blue-700"
                                >
                                    Edit
                                </button>
                                {/* Delete Button */}
                                <Link
                                    href={`/user/${user.username}/work-experiences`}
                                    method="delete"
                                    data={{ id: exp.id }}
                                    className="text-red-500 hover:text-red-700"
                                >
                                    Remove
                                </Link>
                            </div>
                        </div>
                    ))}
            </div>

            {/* Add New Experience Form */}
            <div className="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <form onSubmit={handleSubmit} id="work-experience-form">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium mb-1">
                                Job Position*
                            </label>
                            <input
                                type="text"
                                value={data.position}
                                onChange={(e) =>
                                    setData("position", e.target.value)
                                }
                                className="w-full p-2 border rounded"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">
                                Company Name*
                            </label>
                            <input
                                type="text"
                                value={data.company}
                                onChange={(e) =>
                                    setData("company", e.target.value)
                                }
                                className="w-full p-2 border rounded"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">
                                Start Date*
                            </label>
                            <input
                                type="date"
                                value={data.start_date}
                                onChange={(e) =>
                                    setData("start_date", e.target.value)
                                }
                                className="w-full p-2 border rounded"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">
                                End Date
                            </label>
                            <input
                                type="date"
                                value={data.end_date}
                                onChange={(e) =>
                                    setData("end_date", e.target.value)
                                }
                                className="w-full p-2 border rounded"
                                disabled={data.is_current}
                            />
                            <div className="mt-2 flex items-center">
                                <input
                                    type="checkbox"
                                    id={`current-job`}
                                    checked={data.is_current}
                                    onChange={handleCurrentJobChange}
                                    className="mr-2"
                                />
                                <label
                                    htmlFor={`current-job`}
                                    className="text-sm"
                                >
                                    I currently work here
                                </label>
                            </div>
                        </div>
                    </div>
                    <div className="mt-4">
                        <label className="block text-sm font-medium mb-1">
                            Description
                        </label>
                        <textarea
                            value={data.description}
                            onChange={(e) =>
                                setData("description", e.target.value)
                            }
                            className="w-full p-2 border rounded"
                            rows={3}
                        />
                    </div>
                    {!data.company && <p>Please enter company name</p>}
                    {!data.position && <p>Please enter position</p>}
                    {!data.start_date && <p>Please enter start date</p>}
                    <div className="block text-sm font-medium mb-1">
                        <button
                            className="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:bg-gray-400"
                            disabled={
                                !data.company ||
                                !data.position ||
                                !data.start_date
                            }
                        >
                            {isUpdating ? "Update" : "Add"} Experience
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
